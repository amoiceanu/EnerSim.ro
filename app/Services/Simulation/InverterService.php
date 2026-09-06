<?php

declare(strict_types=1);

namespace App\Services\Simulation;

final class InverterService
{
    public function limit(int $pvW, int $limitW): array
    {
        $actual = min($pvW, $limitW);

        return ['power_w' => $actual, 'clipped_w' => max(0, $pvW - $actual), 'overload' => $pvW > $limitW];
    }
}
