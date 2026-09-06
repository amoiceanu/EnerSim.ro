<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\SimulationState;
use App\Http\Requests\SimulationTickRequest;
use App\Models\Battery;
use App\Models\Consumer;
use App\Models\EquipmentComponent;
use App\Models\Inverter;
use App\Models\Project;
use App\Models\SolarPanel;
use App\Services\Simulation\PowerFlowService;
use App\Services\Simulation\SimulationEngine;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    public function tick(Project $project, SimulationTickRequest $request, SimulationEngine $engine, PowerFlowService $flow): JsonResponse
    {
        $system = $project->systems()->with(['panels', 'inverter', 'battery', 'consumers'])->firstOrFail();
        $d = $request->validated();
        $activePanels = $system->panels->where('enabled', true);
        $panel = $activePanels->first() ?? $system->panels->first();
        $battery = $system->battery;
        $inverter = $system->inverter;
        $state = new SimulationState(time: CarbonImmutable::parse($d['time']), panelCount: $activePanels->isEmpty() ? 0 : 1, panelPowerW: (int) $activePanels->sum('power_w'), orientation: $panel?->orientation ?? 'S', tilt: (int) ($panel?->tilt ?? 35), lossesPercent: (float) ($panel?->losses_percent ?? 15), shadingPercent: (float) ($panel?->shading_percent ?? 0), weather: $d['weather'], randomWeather: (bool) ($d['random_weather'] ?? false), batterySoc: (float) $d['soc'], batteryCapacityKwh: (float) $battery->capacity_kwh, batteryMinSoc: (float) $battery->min_soc, batteryMaxSoc: (float) $battery->max_soc, maxChargeW: $battery->enabled ? $battery->max_charge_power_w : 0, maxDischargeW: $battery->enabled ? $battery->max_discharge_power_w : 0, batteryEfficiency: (float) $battery->efficiency, inverterPowerW: $inverter->nominal_power_w, backupPowerW: $inverter->max_backup_power_w, gridMode: $d['grid_mode'], gridAvailable: (bool) $d['grid_available'], consumers: $system->consumers->toArray(), activeConsumerIds: $d['active_consumers'] ?? [], tickMinutes: (int) ($d['tick_minutes'] ?? 1));
        $result = $engine->tick($state);

        return response()->json($result->toArray() + ['metrics' => $flow->score($result->solarW, $result->servedLoadW, $result->gridW), 'time' => $state->time->toIso8601String()]);
    }

    public function toggleConsumer(Project $project, Consumer $consumer): JsonResponse
    {
        abort_unless($consumer->system->project_id === $project->id, 404);
        $consumer->update(['enabled' => ! $consumer->enabled]);

        return response()->json(['enabled' => $consumer->enabled]);
    }

    public function updateConsumerQuantity(Project $project, Consumer $consumer, Request $request): JsonResponse
    {
        abort_unless($consumer->system->project_id === $project->id, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);
        $consumer->update(['quantity' => $data['quantity']]);

        return response()->json(['quantity' => $consumer->quantity]);
    }

    public function storeConsumer(Project $project, Request $request): JsonResponse
    {
        $data = $request->validate(['preset' => ['required', 'string']]);
        $preset = config('consumer_presets.'.$data['preset']);
        abort_unless(is_array($preset), 422, 'Consumator indisponibil.');

        $system = $project->systems()->firstOrFail();
        $consumer = $system->consumers()->whereIn('name', [$preset['name'], ...($preset['aliases'] ?? [])])->first();
        $created = false;

        if ($consumer) {
            $consumer->update([
                'name' => $preset['name'],
                'category' => $preset['category'],
                'icon' => $preset['icon'],
                'enabled' => true,
                'quantity' => $consumer->enabled ? min(100, $consumer->quantity + 1) : max(1, $consumer->quantity),
            ]);
        } else {
            $consumer = $system->consumers()->create([
                'name' => $preset['name'],
                'category' => $preset['category'],
                'quantity' => 1,
                'nominal_power_w' => $preset['nominal_power_w'],
                'startup_power_w' => $preset['startup_power_w'],
                'startup_duration_seconds' => $preset['startup_power_w'] > 0 ? 2 : 0,
                'priority' => $preset['priority'],
                'is_essential' => $preset['is_essential'],
                'mode' => 'permanent',
                'behavior' => ['hours_per_day' => $preset['hours_per_day']],
                'icon' => $preset['icon'],
                'enabled' => true,
            ]);
            $created = true;
        }

        return response()->json($consumer->fresh(), $created ? 201 : 200);
    }

    public function destroyConsumer(Project $project, Consumer $consumer): JsonResponse
    {
        abort_unless($consumer->system->project_id === $project->id, 404);
        $consumer->delete();

        return response()->json(['deleted' => true]);
    }

    public function storeBattery(Project $project, Request $request): JsonResponse
    {
        $data = $request->validate(['preset' => ['required', 'string']]);
        $component = EquipmentComponent::query()->where('type', 'battery')->where('slug', $data['preset'])->where('active', true)->first();
        abort_unless($component, 422, 'Baterie indisponibilă.');
        $preset = $component->tech_data;

        $system = $project->systems()->with('inverter')->firstOrFail();
        abort_unless($system->inverter?->battery_supported, 422, 'Invertorul nu acceptă baterii.');

        $battery = Battery::updateOrCreate(
            ['system_id' => $system->id],
            [
                'name' => $component->name,
                'chemistry' => $preset['chemistry'],
                'voltage' => $preset['voltage_v'],
                'capacity_kwh' => $preset['capacity_kwh'],
                'capacity_ah' => $preset['capacity_ah'],
                'max_charge_power_w' => $preset['max_power_w'],
                'max_discharge_power_w' => $preset['max_power_w'],
                'efficiency' => 95,
                'min_soc' => 10,
                'max_soc' => 100,
                'current_soc' => 80,
                'cycle_count' => 0,
                'enabled' => true,
            ]
        );

        return response()->json($battery->fresh(), $battery->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyBattery(Project $project): JsonResponse
    {
        $system = $project->systems()->with('battery')->firstOrFail();
        abort_if(! $system->battery, 404);
        $system->battery->update(['enabled' => false]);

        return response()->json($system->battery->fresh());
    }

    public function togglePanel(Project $project, SolarPanel $panel): JsonResponse
    {
        abort_unless($panel->system->project_id === $project->id, 404);
        $panel->update(['enabled' => ! $panel->enabled]);

        return response()->json(['enabled' => $panel->enabled]);
    }

    public function storePanels(Project $project, Request $request): JsonResponse
    {
        $data = $request->validate([
            'preset' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $component = EquipmentComponent::query()->where('type', 'panel')->where('slug', $data['preset'])->where('active', true)->first();
        abort_unless($component, 422, 'Model de panou indisponibil.');
        $preset = $component->tech_data;

        $system = $project->systems()->withCount('panels')->firstOrFail();
        abort_if($system->panels_count + $data['quantity'] > 200, 422, 'Un proiect poate include maximum 200 de panouri.');
        $nextSlot = ((int) $system->panels()->max('slot')) + 1;
        $panels = collect(range(0, $data['quantity'] - 1))->map(function (int $offset) use ($system, $component, $preset, $nextSlot) {
            $slot = $nextSlot + $offset;

            return $system->panels()->create([
                'name' => $component->name,
                'power_w' => $preset['power_w'],
                'orientation' => 'S',
                'tilt' => 35,
                'losses_percent' => 15,
                'shading_percent' => 0,
                'enabled' => true,
                'slot' => $slot,
            ]);
        });

        return response()->json($panels, 201);
    }

    public function duplicatePanel(Project $project, SolarPanel $panel): JsonResponse
    {
        abort_unless($panel->system->project_id === $project->id, 404);
        abort_if($panel->system->panels()->count() >= 200, 422, 'Un proiect poate include maximum 200 de panouri.');

        $nextSlot = ((int) $panel->system->panels()->max('slot')) + 1;
        $duplicate = $panel->replicate();
        $duplicate->slot = $nextSlot;
        $duplicate->name = preg_replace('/\s+#\d+$/', '', $panel->name);
        $duplicate->save();

        return response()->json($duplicate->fresh(), 201);
    }

    public function destroyPanel(Project $project, SolarPanel $panel): JsonResponse
    {
        abort_unless($panel->system->project_id === $project->id, 404);
        $panel->delete();

        return response()->json(['deleted' => true]);
    }

    public function storeInverter(Project $project, Request $request): JsonResponse
    {
        $data = $request->validate(['preset' => ['required', 'string']]);
        $component = EquipmentComponent::query()->where('type', 'inverter')->where('slug', $data['preset'])->where('active', true)->first();
        abort_unless($component, 422, 'Invertor indisponibil.');
        $preset = $component->tech_data;
        $system = $project->systems()->firstOrFail();

        $inverter = Inverter::updateOrCreate(['system_id' => $system->id], [
            'name' => $component->name,
            'nominal_power_w' => $preset['nominal_power_w'],
            'max_pv_power_w' => $preset['max_pv_power_w'],
            'max_backup_power_w' => $preset['max_backup_power_w'],
            'surge_power_w' => $preset['surge_power_w'],
            'efficiency' => $preset['efficiency_percent'],
            'hybrid' => $preset['hybrid'],
            'battery_supported' => true,
            'zero_export_supported' => true,
        ]);

        return response()->json($inverter->fresh());
    }
}
