<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

final readonly class SimulationState
{
    public function __construct(
        public CarbonImmutable $time, public int $panelCount = 4, public int $panelPowerW = 505, public string $orientation = 'S', public int $tilt = 35,
        public float $lossesPercent = 15, public float $shadingPercent = 0, public float $temperatureC = 25, public string $weather = 'clear',
        public bool $randomWeather = false, public float $batterySoc = 80, public float $batteryCapacityKwh = 5.12, public float $batteryMinSoc = 10,
        public float $batteryMaxSoc = 100, public int $maxChargeW = 2500, public int $maxDischargeW = 2500, public float $batteryEfficiency = 95,
        public int $inverterPowerW = 3600, public int $backupPowerW = 3600, public string $gridMode = 'prosumer', public bool $gridAvailable = true,
        public array $consumers = [], public array $activeConsumerIds = [], public int $tickMinutes = 1,
    ) {}
}
