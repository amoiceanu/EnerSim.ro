<?php

declare(strict_types=1);

namespace App\Services\Simulation;

use App\DTOs\SimulationResult;
use App\DTOs\SimulationState;

final class SimulationEngine
{
    public function __construct(private readonly SolarProductionService $solar, private readonly ConsumptionService $consumption, private readonly BatteryService $battery, private readonly InverterService $inverter, private readonly GridService $grid) {}

    public function tick(SimulationState $state): SimulationResult
    {
        $s = $this->solar->calculate($state);
        $i = $this->inverter->limit($s['power_w'], $state->inverterPowerW);
        $c = $this->consumption->calculate($state);
        $load = $c['power_w'];
        $events = [];
        $shed = [];
        if ($i['clipped_w'] > 0) {
            $events[] = ['level' => 'WARNING', 'message' => 'Invertor clipping: '.$i['clipped_w'].' W'];
        }
        if (! $state->gridAvailable) {
            $events[] = ['level' => 'CRITICAL', 'message' => 'Pană de rețea detectată · EPS activ'];
            foreach ($c['active'] as $consumer) {
                if (! ($consumer['is_essential'] ?? false)) {
                    $load -= $consumer['current_power_w'];
                    $shed[] = $consumer['name'];
                }
            }
        }
        $b = $this->battery->dispatch($i['power_w'] - $load, $state->batterySoc, $state->batteryCapacityKwh, $state->batteryMinSoc, $state->batteryMaxSoc, $state->maxChargeW, $state->maxDischargeW, $state->batteryEfficiency, $state->tickMinutes);
        $remaining = $i['power_w'] - $load - $b['power_w'];
        $g = $this->grid->balance($remaining, $state->gridMode, $state->gridAvailable);
        $served = max(0, $load - $g['unserved_w']);
        if ($g['unserved_w'] > 0) {
            $events[] = ['level' => 'WARNING', 'message' => 'Load shedding: '.$g['unserved_w'].' W nealimentați'];
        }

        return new SimulationResult($s['power_w'], $i['power_w'], $c['power_w'], $served, $b['power_w'], $g['grid_w'], $b['soc'], $s['weather'], $events, $shed, $i['clipped_w'], $g['curtailed_w']);
    }
}
