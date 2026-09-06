<?php

declare(strict_types=1);

namespace App\Services\Simulation;

use App\DTOs\SimulationState;

final class SolarProductionService
{
    public function __construct(private readonly WeatherSimulationService $weather) {}

    public function calculate(SimulationState $state): array
    {
        [$weather,$weatherFactor] = $this->weather->resolve($state->weather, $state->time, $state->randomWeather);
        $month = $state->time->month;
        $seasonal = .72 + .28 * cos(($month - 7) * M_PI / 6);
        $dayLength = 12 + 4 * cos(($month - 6) * M_PI / 6);
        $sunrise = 12 - $dayLength / 2;
        $sunset = 12 + $dayLength / 2;
        $hour = $state->time->hour + $state->time->minute / 60;
        if ($hour <= $sunrise || $hour >= $sunset) {
            return ['power_w' => 0, 'weather' => $weather, 'factor' => 0.0];
        }
        $solarFactor = sin(M_PI * (($hour - $sunrise) / $dayLength));
        $orientation = ['N' => .35, 'NE' => .55, 'E' => .78, 'SE' => .93, 'S' => 1.0, 'SV' => .93, 'V' => .78, 'NV' => .55][$state->orientation] ?? 1.0;
        $tilt = max(.55, 1 - abs($state->tilt - 35) / 100);
        $temperature = max(.75, 1 - max(0, $state->temperatureC - 25) * .004);
        $net = (1 - $state->lossesPercent / 100) * (1 - $state->shadingPercent / 100);
        $power = (int) round($state->panelCount * $state->panelPowerW * $seasonal * $solarFactor * $weatherFactor * $orientation * $tilt * $temperature * $net);

        return ['power_w' => max(0, $power), 'weather' => $weather, 'factor' => $solarFactor];
    }
}
