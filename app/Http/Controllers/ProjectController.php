<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Battery;
use App\Models\Consumer;
use App\Models\Inverter;
use App\Models\Project;
use App\Models\SolarPanel;
use App\Models\SystemBattery;
use App\Models\SystemInverter;
use App\Models\SystemSolarPanel;
use App\Models\System;
use App\Services\Reports\ProjectReportService;
use App\Services\Simulation\ProjectAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectAssessmentService $assessment, ProjectReportService $reports): View|RedirectResponse
    {
        if ($request->user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $projects = Project::query()->where(function ($query) use ($request): void {
                $query->where('owner_ip_hash', $this->ipHash($request))->orWhereNull('owner_ip_hash');
            })
            ->with(['systems.panels', 'systems.inverter', 'systems.battery', 'systems.consumers'])
            ->latest()->get()->map(function (Project $project) use ($assessment, $reports) {
                $projectAssessment = $assessment->assess($project);
                $costDocument = $reports->build($project, 'system-cost', $projectAssessment);

                $project->setAttribute('assessment', $projectAssessment);
                $project->setAttribute('system_cost', $costDocument['metrics'][0]['value'] ?? '—');

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
        $project = DB::transaction(function () use ($data, $request) {
            $project = Project::create([
            'name' => $data['name'], 'owner_ip_hash' => $this->ipHash($request), 'county' => $data['county'], 'city' => $data['city'],
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

            $panelProduct = SolarPanel::query()->where('active', true)->get()
                ->first(fn (SolarPanel $panel) => (int) ($panel->tech_data['power_w'] ?? 0) === (int) $data['panel_power_w'])
                ?? SolarPanel::create($this->legacyProduct('panel', 'Panou '.$data['panel_power_w'].' W', ['power_w' => (int) $data['panel_power_w']]));
            foreach (range(1, (int) $data['panel_count']) as $slot) {
                SystemSolarPanel::create([
                    'system_id' => $system->id, 'solar_panel_id' => $panelProduct->id, 'orientation' => $data['orientation'],
                    'tilt' => $data['tilt'], 'losses_percent' => $data['losses_percent'], 'slot' => $slot,
                ]);
            }

            $inverterProduct = Inverter::query()->where('active', true)->get()
                ->first(fn (Inverter $inverter) => (int) ($inverter->tech_data['nominal_power_w'] ?? 0) === (int) $data['inverter_power_w'])
                ?? Inverter::create($this->legacyProduct('inverter', $data['inverter_name'], [
                    'nominal_power_w' => (int) $data['inverter_power_w'], 'max_pv_power_w' => (int) round($data['inverter_power_w'] * 1.3),
                    'max_backup_power_w' => (int) $data['inverter_power_w'], 'surge_power_w' => (int) round($data['inverter_power_w'] * 2),
                    'efficiency_percent' => 97.6, 'hybrid' => true, 'battery_supported' => true,
                ]));
            SystemInverter::create(['system_id' => $system->id, 'inverter_id' => $inverterProduct->id]);

            $batteryCapacity = $data['battery_enabled'] ? (float) $data['battery_capacity_kwh'] : .5;
            $batteryProduct = Battery::query()->where('active', true)->get()
                ->first(fn (Battery $battery) => (float) ($battery->tech_data['capacity_kwh'] ?? 0) === $batteryCapacity)
                ?? Battery::create($this->legacyProduct('battery', 'Baterie LiFePO4', [
                    'chemistry' => 'LiFePO4', 'voltage_v' => 51.2, 'capacity_kwh' => $batteryCapacity,
                    'capacity_ah' => round($batteryCapacity * 1000 / 51.2, 2), 'max_power_w' => $data['battery_enabled'] ? (int) $data['battery_power_w'] : 100,
                ]));
            SystemBattery::create(['system_id' => $system->id, 'battery_id' => $batteryProduct->id, 'current_soc' => $data['initial_soc'] ?? 80, 'enabled' => $data['battery_enabled'] ?? false]);

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
        $catalog = collect([
            'panel' => SolarPanel::query()->where('active', true)->orderBy('brand')->orderBy('name')->get(),
            'inverter' => Inverter::query()->where('active', true)->orderBy('brand')->orderBy('name')->get(),
            'battery' => Battery::query()->where('active', true)->orderBy('brand')->orderBy('name')->get(),
        ]);
        $catalogItems = $catalog->flatten(1);
        $catalogSummary = [
            'total' => $catalogItems->count(),
            'panels' => ($catalog->get('panel') ?? collect())->count(),
            'inverters' => ($catalog->get('inverter') ?? collect())->count(),
            'batteries' => ($catalog->get('battery') ?? collect())->count(),
            'updated_at' => optional($catalogItems->pluck('source_checked_at')->filter()->max())->format('d.m.Y'),
        ];
        $reportDefinitions = ProjectReportService::definitions();
        $simulatorData = [
            'project_id' => $project->id, 'project_token' => $project->getRouteKey(), 'project_name' => $project->name,
            'project' => $project->only(['name', 'city', 'county', 'latitude', 'longitude']),
            'system' => $system->only(['name', 'timezone']),
            'inverter' => $system->inverter,
            'battery' => $system->battery, 'panels' => $system->panels, 'consumers' => $system->consumers,
            'consumer_presets' => collect(config('consumer_presets'))->map(fn (array $preset, string $key) => ['key' => $key] + $preset)->values(),
            'panel_presets' => ($catalog->get('panel') ?? collect())->map(fn (SolarPanel $item) => [
                'key' => $item->slug, 'name' => $item->name, 'brand' => $item->brand, 'model' => $item->model,
                'power_w' => $item->tech_data['power_w'] ?? 0, 'technology' => $item->tech_data['technology'] ?? 'Nespecificat',
                'efficiency' => $item->tech_data['efficiency_percent'] ?? 0, 'price_lei' => $item->price_lei,
                'stock_status' => $item->stock_status, 'source_url' => $item->source_url,
            ])->values(),
            'battery_presets' => ($catalog->get('battery') ?? collect())->map(fn (Battery $item) => [
                'key' => $item->slug, 'name' => $item->name, 'brand' => $item->brand, 'icon' => '🔋',
                'chemistry' => $item->tech_data['chemistry'] ?? 'Nespecificat', 'voltage' => $item->tech_data['voltage_v'] ?? 0,
                'capacity_kwh' => $item->tech_data['capacity_kwh'] ?? 0, 'capacity_ah' => $item->tech_data['capacity_ah'] ?? 0,
                'power_w' => $item->tech_data['max_power_w'] ?? 0, 'cycles' => $item->tech_data['cycles'] ?? 0,
                'ip_rating' => $item->tech_data['ip_rating'] ?? 'Nespecificat', 'price_lei' => $item->price_lei,
                'stock_status' => $item->stock_status, 'source_url' => $item->source_url,
            ])->values(),
            'inverter_presets' => ($catalog->get('inverter') ?? collect())->map(fn (Inverter $item) => [
                'key' => $item->slug, 'name' => $item->name, 'brand' => $item->brand, 'model' => $item->model,
                'nominal_power_w' => $item->tech_data['nominal_power_w'] ?? 0, 'max_pv_power_w' => $item->tech_data['max_pv_power_w'] ?? 0,
                'efficiency' => $item->tech_data['efficiency_percent'] ?? 0, 'phases' => $item->tech_data['phases'] ?? 1,
                'mppt_count' => $item->tech_data['mppt_count'] ?? 0, 'price_lei' => $item->price_lei,
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

    /** @param array<string, mixed> $techData */
    private function legacyProduct(string $type, string $name, array $techData): array
    {
        return [
            'slug' => 'project-'.Str::slug($type.'-'.$name).'-'.Str::random(8), 'brand' => 'Configurație proiect',
            'name' => $name, 'tech_data' => $techData, 'active' => true,
        ];
    }

    private function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}
