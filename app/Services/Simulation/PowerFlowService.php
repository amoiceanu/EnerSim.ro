<?php

declare(strict_types=1);

namespace App\Services\Simulation;

final class PowerFlowService
{
    public function score(int $solar, int $load, int $grid): array
    {
        $ind = $load > 0 ? max(0, min(100, 100 - max(0, $grid) / $load * 100)) : 100;
        $util = $solar > 0 ? max(0, min(100, ($solar - max(0, -$grid)) / $solar * 100)) : 0;

        return ['independence' => round($ind), 'solar_utilization' => round($util), 'grid_dependency' => round(100 - $ind), 'score' => $ind >= 90 ? 'A' : ($ind >= 75 ? 'B' : ($ind >= 60 ? 'C' : ($ind >= 40 ? 'D' : 'F')))];
    }
}
