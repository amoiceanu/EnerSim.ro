<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\SimulationState;
use App\Services\Simulation\BatteryService;
use App\Services\Simulation\ConsumptionService;
use App\Services\Simulation\SimulationEngine;
use App\Services\Simulation\SolarProductionService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class SimulationEngineTest extends TestCase
{
    private function state(array $overrides = []): SimulationState
    {
        return new SimulationState(...array_merge([
            'time' => CarbonImmutable::parse('2027-07-15 12:00:00'),
            'consumers' => [['id' => 1, 'name' => 'Casă', 'nominal_power_w' => 700, 'standby_power_w' => 0, 'startup_power_w' => 0, 'enabled' => true, 'is_essential' => true, 'priority' => 1, 'mode' => 'permanent']],
        ], $overrides));
    }

    public function test_solar_production_at_midnight_is_zero(): void
    {
        $this->assertSame(0, app(SolarProductionService::class)->calculate($this->state(['time' => CarbonImmutable::parse('2027-07-15 00:00')]))['power_w']);
    }

    public function test_summer_production_exceeds_winter(): void
    {
        $solar = app(SolarProductionService::class);
        $this->assertGreaterThan($solar->calculate($this->state(['time' => CarbonImmutable::parse('2027-01-15 12:00')]))['power_w'], $solar->calculate($this->state())['power_w']);
    }

    public function test_battery_soc_never_exceeds_maximum(): void
    {
        $this->assertLessThanOrEqual(100, app(BatteryService::class)->dispatch(10000, 99.9, 5.12, 10, 100, 2500, 2500, 95, 60)['soc']);
    }

    public function test_battery_soc_never_drops_below_minimum(): void
    {
        $this->assertGreaterThanOrEqual(10, app(BatteryService::class)->dispatch(-10000, 10.1, 5.12, 10, 100, 2500, 2500, 95, 60)['soc']);
    }

    public function test_zero_export_does_not_inject_energy(): void
    {
        $r = app(SimulationEngine::class)->tick($this->state(['gridMode' => 'zero_export', 'batterySoc' => 100]));
        $this->assertSame(0, $r->gridW);
    }

    public function test_prosumer_mode_exports_surplus(): void
    {
        $r = app(SimulationEngine::class)->tick($this->state(['gridMode' => 'prosumer', 'batterySoc' => 100]));
        $this->assertLessThan(0, $r->gridW);
    }

    public function test_grid_failure_enables_eps_and_sheds_nonessential_loads(): void
    {
        $cons = [['id' => 1, 'name' => 'Frigider', 'nominal_power_w' => 120, 'enabled' => true, 'is_essential' => true, 'priority' => 1, 'mode' => 'permanent'], ['id' => 2, 'name' => 'Boiler', 'nominal_power_w' => 2000, 'enabled' => true, 'is_essential' => false, 'priority' => 3, 'mode' => 'permanent']];
        $r = app(SimulationEngine::class)->tick($this->state(['gridAvailable' => false, 'gridMode' => 'offline', 'consumers' => $cons, 'time' => CarbonImmutable::parse('2027-01-15 00:00'), 'batterySoc' => 10]));
        $this->assertContains('Boiler', $r->shedLoads);
        $this->assertNotContains('Frigider', $r->shedLoads);
        $this->assertStringContainsString('EPS', $r->events[0]['message']);
    }

    public function test_inverter_overload_generates_warning(): void
    {
        $r = app(SimulationEngine::class)->tick($this->state(['panelCount' => 20, 'inverterPowerW' => 1000, 'batterySoc' => 100]));
        $this->assertGreaterThan(0, $r->clippedW);
        $this->assertSame('WARNING', $r->events[0]['level']);
    }

    public function test_hydrofor_startup_spike_is_calculated(): void
    {
        $c = ['id' => 7, 'name' => 'Hidrofor', 'quantity' => 3, 'nominal_power_w' => 900, 'startup_power_w' => 2500, 'standby_power_w' => 0, 'enabled' => true, 'is_essential' => true, 'priority' => 1, 'mode' => 'permanent', 'just_started' => true];
        $this->assertSame(7500, app(ConsumptionService::class)->calculate($this->state(['consumers' => [$c]]))['power_w']);
    }

    public function test_panel_nominal_totals(): void
    {
        $this->assertSame(2020,4 * 505);
        $this->assertSame(3030,6 * 505);
    }
}
