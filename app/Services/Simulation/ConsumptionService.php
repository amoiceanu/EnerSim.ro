<?php

declare(strict_types=1);

namespace App\Services\Simulation;

use App\DTOs\SimulationState;

final class ConsumptionService
{
    public function calculate(SimulationState $state): array
    {
        $active = [];
        $total = 0;
        $minute = (int) $state->time->format('i');
        foreach ($state->consumers as $consumer) {
            if (! ($consumer['enabled'] ?? true)) {
                continue;
            } $mode = $consumer['mode'] ?? 'permanent';
            $on = $mode === 'permanent' || in_array($consumer['id'] ?? 0, $state->activeConsumerIds, true);
            if ($mode === 'cycle') {
                $cycle = (int) ($consumer['behavior']['cycle_minutes'] ?? 45);
                $run = (int) ($consumer['behavior']['run_minutes'] ?? 15);
                $on = (($state->time->hour * 60 + $minute) % $cycle) < $run;
            }
            if ($mode === 'random') {
                $frequency = (int) ($consumer['behavior']['times_per_day'] ?? 10);
                $on = (abs(crc32($state->time->format('Y-m-d-H-i').($consumer['id'] ?? 0))) % max(1, (1440 / $frequency))) === 0;
            }
            $quantity = max(1, (int) ($consumer['quantity'] ?? 1));
            $watts = ($on ? (int) $consumer['nominal_power_w'] : (int) ($consumer['standby_power_w'] ?? 0)) * $quantity;
            if ($on && ($consumer['just_started'] ?? false)) {
                $watts = max($watts, (int) ($consumer['startup_power_w'] ?? 0) * $quantity);
            }
            $total += $watts;
            if ($on) {
                $active[] = $consumer + ['current_power_w' => $watts];
            }
        }

        return ['power_w' => $total, 'active' => $active];
    }
}
