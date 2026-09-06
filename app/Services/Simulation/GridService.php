<?php

declare(strict_types=1);

namespace App\Services\Simulation;

final class GridService
{
    public function balance(int $remainingW, string $mode, bool $available): array
    {
        if (! $available || $mode === 'offline') {
            return ['grid_w' => 0, 'unserved_w' => max(0, -$remainingW), 'curtailed_w' => max(0, $remainingW)];
        }if ($remainingW > 0 && $mode === 'zero_export') {
            return ['grid_w' => 0, 'unserved_w' => 0, 'curtailed_w' => $remainingW];
        }

return ['grid_w' => -$remainingW, 'unserved_w' => 0, 'curtailed_w' => 0];
    }
}
