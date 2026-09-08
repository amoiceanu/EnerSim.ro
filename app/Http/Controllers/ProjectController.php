<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Battery;
use App\Models\Consumer;
use App\Models\EquipmentComponent;
use App\Models\Inverter;
use App\Models\Project;
use App\Models\SolarPanel;
use App\Models\System;
use App\Services\Reports\ProjectReportService;
use App\Services\Simulation\ProjectAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(ProjectAssessmentService $assessment): View
    {
        $projects = Project::with(['systems.panels', 'systems.inverter', 'systems.battery', 'systems.consumers'])
            ->latest()->get()->map(function (Project $project) use ($assessment) {
                $project->setAttribute('assessment', $assessment->assess($project));

                return $project;
            });

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $project = DB::transaction(function () use ($data) {
            $project = Project::create([
                'name' => $data['name'], 'county' => $data['county'], 'city' => $data['city'],
                'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $system = System::create([
                'project_id' => $project->id, 'name' => $data['system_name'], 'country' => 'Romania',
                'city' => $data['city'], 'latitude' => $data['latitude'] ?? 44.4268,
                'longitude' => $data['longitude'] ?? 26.1025,
            ]);

            foreach ($data['consumers'] as $index => $consumer) {
                Consumer::create([
                    'system_id' => $system->id, 'name' => $consumer['name'], 'category' => 'household',
                    'quantity' => $consumer['quantity'] ?? 1,
                    'nominal_power_w' => $consumer['nominal_power_w'],
                    'startup_power_w' => $consumer['startup_power_w'] ?? 0,
                    'startup_duration_seconds' => ($consumer['startup_power_w'] ?? 0) > 0 ? 2 : 0,
                    'priority' => $consumer['priority'], 'is_essential' => $consumer['is_essential'] ?? false,
                    'mode' => 'permanent', 'behavior' => ['hours_per_day' => (float) $consumer['hours_per_day']],
                    'icon' => $index % 2 === 0 ? '⚡' : '⌁',
                ]);
            }

            foreach (range(1, (int) $data['panel_count']) as $slot) {
                SolarPanel::create([
                    'system_id' => $system->id, 'name' => 'Panou '.$slot, 'power_w' => $data['panel_power_w'],
                    'orientation' => $data['orientation'], 'tilt' => $data['tilt'],
                    'losses_percent' => $data['losses_percent'], 'slot' => $slot,
                ]);
            }

            Inverter::create([
                'system_id' => $system->id, 'name' => $data['inverter_name'],
                'nominal_power_w' => $data['inverter_power_w'],
                'max_pv_power_w' => (int) round($data['inverter_power_w'] * 1.3),
                'max_backup_power_w' => $data['inverter_power_w'],
                'surge_power_w' => (int) round($data['inverter_power_w'] * 2),
            ]);

            Battery::create([
                'system_id' => $system->id, 'name' => 'Baterie LiFePO4', 'voltage' => 51.2,
                'capacity_kwh' => $data['battery_enabled'] ? $data['battery_capacity_kwh'] : 0.5,
                'capacity_ah' => $data['battery_enabled'] ? round($data['battery_capacity_kwh'] * 1000 / 51.2, 2) : 10,
                'max_charge_power_w' => $data['battery_enabled'] ? $data['battery_power_w'] : 100,
                'max_discharge_power_w' => $data['battery_enabled'] ? $data['battery_power_w'] : 100,
                'current_soc' => $data['initial_soc'] ?? 80, 'enabled' => $data['battery_enabled'] ?? false,
            ]);

            return $project;
        });

        return redirect()->route('projects.show', $project)->with('success', 'Proiectul a fost creat. Poți porni simularea.');
    }

    public function show(Project $project, ProjectAssessmentService $assessment): View
    {
        $project->load(['systems.panels', 'systems.inverter', 'systems.battery', 'systems.consumers', 'systems.scenarios']);
        $system = $project->systems->firstOrFail();
        $projects = Project::select(['id', 'name'])->orderBy('name')->get();
        $assessment = $assessment->assess($project);
        $catalogItems = EquipmentComponent::query()->where('active', true)->orderBy('brand')->orderBy('name')->get();
        $catalog = $catalogItems->groupBy('type');
        $catalogSummary = [
            'total' => $catalogItems->count(),
            'panels' => ($catalog->get('panel') ?? collect())->count(),
            'inverters' => ($catalog->get('inverter') ?? collect())->count(),
            'batteries' => ($catalog->get('battery') ?? collect())->count(),
            'updated_at' => optional($catalogItems->max('source_checked_at'))->format('d.m.Y'),
        ];
        $reportDefinitions = ProjectReportService::definitions();
        $simulatorData = [
            'project_id' => $project->id, 'project_token' => $project->getRouteKey(), 'project_name' => $project->name,
            'project' => $project->only(['name', 'city', 'county', 'latitude', 'longitude']),
            'system' => $system->only(['name', 'timezone']),
            'inverter' => $system->inverter,
            'battery' => $system->battery, 'panels' => $system->panels, 'consumers' => $system->consumers,
            'consumer_presets' => collect(config('consumer_presets'))->map(fn (array $preset, string $key) => ['key' => $key] + $preset)->values(),
            'panel_presets' => ($catalog->get('panel') ?? collect())->map(fn (EquipmentComponent $item) => [
                'key' => $item->slug, 'name' => $item->name, 'brand' => $item->brand, 'model' => $item->model,
                'power_w' => $item->tech_data['power_w'], 'technology' => $item->tech_data['technology'],
                'efficiency' => $item->tech_data['efficiency_percent'], 'price_lei' => $item->price_lei,
                'stock_status' => $item->stock_status, 'source_url' => $item->source_url,
            ])->values(),
            'battery_presets' => ($catalog->get('battery') ?? collect())->map(fn (EquipmentComponent $item) => [
                'key' => $item->slug, 'name' => $item->name, 'brand' => $item->brand, 'icon' => '🔋',
                'chemistry' => $item->tech_data['chemistry'], 'voltage' => $item->tech_data['voltage_v'],
                'capacity_kwh' => $item->tech_data['capacity_kwh'], 'capacity_ah' => $item->tech_data['capacity_ah'],
                'power_w' => $item->tech_data['max_power_w'], 'cycles' => $item->tech_data['cycles'],
                'ip_rating' => $item->tech_data['ip_rating'], 'price_lei' => $item->price_lei,
                'stock_status' => $item->stock_status, 'source_url' => $item->source_url,
            ])->values(),
            'inverter_presets' => ($catalog->get('inverter') ?? collect())->map(fn (EquipmentComponent $item) => [
                'key' => $item->slug, 'name' => $item->name, 'brand' => $item->brand, 'model' => $item->model,
                'nominal_power_w' => $item->tech_data['nominal_power_w'], 'max_pv_power_w' => $item->tech_data['max_pv_power_w'],
                'efficiency' => $item->tech_data['efficiency_percent'], 'phases' => $item->tech_data['phases'],
                'mppt_count' => $item->tech_data['mppt_count'], 'price_lei' => $item->price_lei,
                'stock_status' => $item->stock_status, 'source_url' => $item->source_url,
            ])->sortBy('nominal_power_w')->values(),
        ];

        return view('dashboard.index', compact('project', 'projects', 'system', 'assessment', 'simulatorData', 'catalogSummary', 'reportDefinitions'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proiectul a fost șters.');
    }
}
