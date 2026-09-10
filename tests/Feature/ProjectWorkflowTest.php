<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\Simulation\ProjectAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_independent_project_through_the_wizard(): void
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Casa Brașov', 'county' => 'Brașov', 'city' => 'Brașov',
            'consumers' => [
                ['name' => 'Frigider', 'quantity' => 1, 'nominal_power_w' => 120, 'startup_power_w' => 700, 'hours_per_day' => 8, 'priority' => 1, 'is_essential' => 1],
                ['name' => 'Boiler', 'quantity' => 2, 'nominal_power_w' => 2000, 'startup_power_w' => 0, 'hours_per_day' => 2, 'priority' => 3, 'is_essential' => 0],
            ],
            'system_name' => 'Sistem principal', 'panel_count' => 6, 'panel_power_w' => 505,
            'orientation' => 'S', 'tilt' => 35, 'losses_percent' => 15,
            'inverter_name' => 'Deye 5 kW', 'inverter_power_w' => 5000,
            'battery_enabled' => 1, 'battery_capacity_kwh' => 5.12, 'battery_power_w' => 2500, 'initial_soc' => 80,
        ]);

        $project = Project::with('systems.panels', 'systems.consumers')->firstOrFail();
        $response->assertRedirect(route('projects.show', $project));
        $this->assertCount(6, $project->systems->first()->panels);
        $this->assertCount(2, $project->systems->first()->consumers);
        $this->assertSame(2, $project->systems->first()->consumers->firstWhere('name', 'Boiler')->quantity);
    }

    public function test_assessment_reports_whether_the_system_can_supply_consumers(): void
    {
        $this->seed();
        $project = Project::with(['systems.panels', 'systems.inverter', 'systems.battery', 'systems.consumers'])->firstOrFail();
        $assessment = app(ProjectAssessmentService::class)->assess($project);

        $this->assertContains($assessment['status'], ['adequate', 'partial', 'insufficient']);
        $this->assertCount(4, $assessment['checks']);
        $this->assertArrayHasKey('recommendations', $assessment);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Selectați în proiect')
            ->assertSee('Disponibili')
            ->assertSee('Trage aici aparate')
            ->assertSee('draggable="true"', false)
            ->assertSee('dropConsumer($event)', false)
            ->assertSee('Cantitate')
            ->assertSee('Poți trage aparatele')
            ->assertSee('masina-electrica')
            ->assertSee('masina-phev')
            ->assertSee('triciclu-electric')
            ->assertSee('pompa-apa')
            ->assertSee('centrala-lemne')
            ->assertSee('lada-frigorifica')
            ->assertSee('Sistemul nu include baterii')
            ->assertSee('vtac-vt-12040-1')
            ->assertSee('Cost estimat baterie')
            ->assertSee('Preț catalog SolarTech')
            ->assertSee('Alege momentul simulării')
            ->assertSee('Luna')
            ->assertSee('Ziua')
            ->assertSee('Ora')
            ->assertSee('Tipul zilei')
            ->assertSee('setSimulationWeather(option.id)', false)
            ->assertSee('Consum vs producție')
            ->assertSee('Acoperire directă din solar')
            ->assertSee('Centru de rapoarte')
            ->assertSee('Alege raportul dorit')
            ->assertSee('Document A4')
            ->assertSee('Rapoarte gata pentru export')
            ->assertSee('target="_blank"', false)
            ->assertSee('Panourile instalate')
            ->assertSee('Adaugă unul')
            ->assertDontSee('panel-item-quantity', false)
            ->assertSee('Invertoare SolarTech')
            ->assertSee('Alege invertorul')
            ->assertSee('Consumatorii proiectului')
            ->assertSee('Bateriile proiectului')
            ->assertSee('Locație și condiții meteo')
            ->assertSee('Setările sistemului')
            ->assertSee('Ghid de utilizare')
            ->assertSee('Scopul aplicației')
            ->assertSee('Sfaturi Pro')
            ->assertSee('Închide sfatul')
            ->assertSee('dashboard-empty-page', false)
            ->assertSee("proTipVisible && activeNav!=='dashboard'", false)
            ->assertDontSee('Grafic Producție vs Consum')
            ->assertDontSee('Activitate Simulator')
            ->assertSee('deye-sun-3-6k-sg05lp1')
            ->assertSee('deye-sun-6k-sg05lp1')
            ->assertSee('deye-sun-10k-sg05lp3');

    }

    public function test_each_report_opens_as_a_print_ready_a4_document(): void
    {
        $this->seed();
        $project = Project::firstOrFail();
        $reports = [
            'system-cost' => 'Costul sistemului',
            'daily-balance' => 'Bilanț energetic zilnic',
            'monthly-estimate' => 'Estimare lunară',
            'independence' => 'Autoconsum și independență',
            'consumers' => 'Analiză consumatori',
            'pv-performance' => 'Performanță sistem FV',
            'battery-backup' => 'Baterie și backup',
            'simulation-results' => 'Rezultate simulare sistem și consumatori',
        ];

        foreach ($reports as $type => $title) {
            $this->get(route('reports.show', [$project, $type]))
                ->assertOk()
                ->assertSee($title)
                ->assertSee('Document A4')
                ->assertSee('Exportă PDF')
                ->assertSee('Metodologie și limitări')
                ->assertSee('report-toolbar', false)
                ->assertSee('report-project-meta', false)
                ->assertSee('report-kpis', false)
                ->assertSee('report-table-scroll', false)
                ->assertSee('report-callout', false)
                ->assertSee('<svg', false);
        }

        $this->get(route('reports.show', [$project, 'simulation-results']))
            ->assertOk()
            ->assertSee('Ianuarie')
            ->assertSee('Decembrie')
            ->assertSee('14:00')
            ->assertSee('Starea vremii')
            ->assertSee('Consumatori incluși')
            ->assertSee('Luni cu acoperire completă');

        $this->get(route('reports.show', [$project, 'inexistent']))->assertNotFound();
    }

    public function test_simulation_tick_uses_the_selected_project(): void
    {
        $this->seed();
        $project = Project::firstOrFail();

        $daytime = $this->postJson(route('simulation.tick', $project), [
            'time' => '2027-07-15T12:00:00+03:00', 'soc' => 80, 'weather' => 'clear',
            'grid_mode' => 'prosumer', 'grid_available' => true, 'active_consumers' => [], 'tick_minutes' => 1,
        ])->assertOk()->assertJsonStructure(['solar_w', 'load_w', 'soc', 'metrics', 'time']);

        $this->assertGreaterThan(0, $daytime->json('solar_w'));

        $cloudy = $this->postJson(route('simulation.tick', $project), [
            'time' => '2027-07-15T12:00:00+03:00', 'soc' => 80, 'weather' => 'cloudy',
            'grid_mode' => 'prosumer', 'grid_available' => true, 'active_consumers' => [], 'tick_minutes' => 1,
        ])->assertOk();

        $this->assertLessThan($daytime->json('solar_w'), $cloudy->json('solar_w'));

        $this->postJson(route('simulation.tick', $project), [
            'time' => '2027-07-15T00:00:00+03:00', 'soc' => 80, 'weather' => 'clear',
            'grid_mode' => 'prosumer', 'grid_available' => true, 'active_consumers' => [], 'tick_minutes' => 1,
        ])->assertOk()->assertJsonPath('solar_w', 0);

        $this->postJson(route('simulation.tick', $project), [
            'time' => '2027-07-15T18:00:00+03:00', 'soc' => 80, 'weather' => 'clear',
            'grid_mode' => 'offline', 'grid_available' => false, 'active_consumers' => [], 'tick_minutes' => 5,
        ])->assertOk()->assertJsonPath('grid_w', 0);
    }

    public function test_consumer_can_be_added_from_catalog_and_removed_from_project(): void
    {
        $this->seed();
        $project = Project::with('systems.consumers')->firstOrFail();

        $response = $this->postJson(route('consumers.store', $project), ['preset' => 'masina-electrica'])
            ->assertCreated()
            ->assertJsonPath('name', 'Mașină electrică')
            ->assertJsonPath('icon', '🚗');

        $consumerId = $response->json('id');
        $this->assertDatabaseHas('consumers', ['id' => $consumerId, 'system_id' => $project->systems->first()->id]);

        $router = $project->systems->first()->consumers()->where('name', 'Router')->firstOrFail();
        $this->postJson(route('consumers.store', $project), ['preset' => 'router'])
            ->assertOk()
            ->assertJsonPath('id', $router->id)
            ->assertJsonPath('name', 'Router & internet')
            ->assertJsonPath('quantity', 2)
            ->assertJsonPath('icon', '📶');
        $this->assertDatabaseMissing('consumers', ['system_id' => $router->system_id, 'name' => 'Router']);

        $phev = $this->postJson(route('consumers.store', $project), ['preset' => 'masina-phev'])
            ->assertCreated()
            ->assertJsonPath('name', 'Mașină PHEV')
            ->assertJsonPath('icon', '🚙')
            ->assertJsonPath('nominal_power_w', 3700);
        $this->assertNotSame($consumerId, $phev->json('id'));

        $this->postJson(route('consumers.store', $project), ['preset' => 'triciclu-electric'])
            ->assertCreated()
            ->assertJsonPath('name', 'Triciclu electric')
            ->assertJsonPath('icon', '🛺')
            ->assertJsonPath('nominal_power_w', 800);

        $this->postJson(route('consumers.store', $project), ['preset' => 'pompa-apa'])
            ->assertCreated()
            ->assertJsonPath('name', 'Pompă de apă')
            ->assertJsonPath('icon', '🚰')
            ->assertJsonPath('nominal_power_w', 1100)
            ->assertJsonPath('startup_power_w', 3000);

        $project->systems->first()->consumers()->where('name', 'Centrală pe lemne')->delete();
        $this->postJson(route('consumers.store', $project), ['preset' => 'centrala-lemne'])
            ->assertCreated()
            ->assertJsonPath('name', 'Centrală pe lemne')
            ->assertJsonPath('icon', '🔥')
            ->assertJsonPath('nominal_power_w', 150)
            ->assertJsonPath('is_essential', true);

        $this->deleteJson(route('consumers.destroy', [$project, $consumerId]))
            ->assertOk()
            ->assertJson(['deleted' => true]);
        $this->assertDatabaseMissing('consumers', ['id' => $consumerId]);
    }

    public function test_consumer_quantity_can_be_updated(): void
    {
        $this->seed();
        $project = Project::with('systems.consumers')->firstOrFail();
        $consumer = $project->systems->first()->consumers->firstOrFail();

        $this->patchJson(route('consumers.quantity', [$project, $consumer]), ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('quantity', 3);

        $this->assertDatabaseHas('consumers', ['id' => $consumer->id, 'quantity' => 3]);
    }

    public function test_battery_can_be_selected_from_catalog_and_removed_from_system(): void
    {
        $this->seed();
        $project = Project::with('systems.battery')->firstOrFail();
        $battery = $project->systems->first()->battery;

        $this->deleteJson(route('battery.destroy', $project))
            ->assertOk()
            ->assertJsonPath('enabled', false);
        $this->assertDatabaseHas('system_batteries', ['id' => $battery->id, 'enabled' => false]);

        $this->postJson(route('battery.store', $project), ['preset' => 'vtac-vt-12040-1'])
            ->assertOk()
            ->assertJsonPath('name', 'V-TAC LiFePO4 10.24kWh IP65')
            ->assertJsonPath('enabled', true);
        $this->assertDatabaseHas('system_batteries', ['id' => $battery->id, 'enabled' => true]);
    }

    public function test_solar_panels_can_be_added_from_catalog_and_removed(): void
    {
        $this->seed();
        $project = Project::with('systems.panels')->firstOrFail();
        $system = $project->systems->first();
        $initialCount = $system->panels()->count();

        $response = $this->postJson(route('panels.store', $project), [
            'preset' => 'aiko-stellar-1n-645',
            'quantity' => 2,
        ])->assertCreated()->assertJsonCount(2)->assertJsonPath('0.name', 'AIKO Stellar 1N+ 645W Bifacial');

        $panelId = $response->json('0.id');
        $this->assertDatabaseCount('system_solar_panels', $initialCount + 2);
        $this->assertDatabaseHas('system_solar_panels', ['id' => $panelId, 'system_id' => $system->id]);

        $duplicate = $this->postJson(route('panels.duplicate', [$project, $panelId]))
            ->assertCreated()
            ->assertJsonPath('system_id', $system->id)
            ->assertJsonPath('name', 'AIKO Stellar 1N+ 645W Bifacial')
            ->assertJsonPath('power_w', 645)
            ->assertJsonPath('orientation', $response->json('0.orientation'));
        $this->assertNotSame($panelId, $duplicate->json('id'));
        $this->assertDatabaseCount('system_solar_panels', $initialCount + 3);

        $this->deleteJson(route('panels.destroy', [$project, $panelId]))
            ->assertOk()
            ->assertJson(['deleted' => true]);
        $this->assertDatabaseMissing('system_solar_panels', ['id' => $panelId]);
    }

    public function test_inverter_can_be_selected_from_equipment_catalog(): void
    {
        $this->seed();
        $project = Project::firstOrFail();

        $this->putJson(route('inverter.store', $project), ['preset' => 'deye-sun-6k-sg05lp1'])
            ->assertOk()
            ->assertJsonPath('name', 'Deye Hibrid 6kW Monofazat')
            ->assertJsonPath('nominal_power_w', 6000)
            ->assertJsonPath('max_pv_power_w', 9600);
    }
}
