<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class SimulationResult
{
    public function __construct(public int $solarPotentialW, public int $solarW, public int $loadW, public int $servedLoadW, public int $batteryW, public int $gridW, public float $soc, public string $weather, public array $events = [], public array $shedLoads = [], public int $clippedW = 0, public int $curtailedW = 0) {}

    public function toArray(): array
    {
        return ['solar_potential_w' => $this->solarPotentialW, 'solar_w' => $this->solarW, 'load_w' => $this->loadW, 'served_load_w' => $this->servedLoadW, 'battery_w' => $this->batteryW, 'grid_w' => $this->gridW, 'soc' => round($this->soc, 2), 'weather' => $this->weather, 'events' => $this->events, 'shed_loads' => $this->shedLoads, 'clipped_w' => $this->clippedW, 'curtailed_w' => $this->curtailedW];
    }
}
