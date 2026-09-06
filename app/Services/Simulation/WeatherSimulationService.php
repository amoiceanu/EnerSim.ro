<?php

declare(strict_types=1);

namespace App\Services\Simulation;

use Carbon\CarbonImmutable;

final class WeatherSimulationService
{
    private const RANGES = ['clear' => [.95, 1.0], 'partly_cloudy' => [.7, .9], 'cloudy' => [.3, .6], 'rain' => [.2, .4], 'fog' => [.2, .5], 'snow' => [.05, .3]];

    public function resolve(string $weather, CarbonImmutable $time, bool $random = false): array
    {
        if ($random || $weather === 'random') {
            $types = array_keys(self::RANGES);
            $weather = $types[abs(crc32($time->format('Y-m-d-H'))) % count($types)];
        }
        [$min,$max] = self::RANGES[$weather] ?? self::RANGES['clear'];
        $seed = (abs(crc32($time->format('Y-m-d-H-i'))) % 1000) / 1000;

        return [$weather, $min + ($max - $min) * $seed];
    }
}
