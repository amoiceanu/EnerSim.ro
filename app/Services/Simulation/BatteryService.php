<?php

declare(strict_types=1);

namespace App\Services\Simulation;

final class BatteryService
{
    public function dispatch(int $netW, float $soc, float $capacityKwh, float $minSoc, float $maxSoc, int $maxChargeW, int $maxDischargeW, float $efficiency, int $tickMinutes): array
    {
        $hours = $tickMinutes / 60;
        $eff = $efficiency / 100;
        $batteryW = 0;
        if ($netW > 0 && $soc < $maxSoc) {
            $roomWh = $capacityKwh * 1000 * ($maxSoc - $soc) / 100;
            $batteryW = min($netW, $maxChargeW, (int) floor($roomWh / max($hours * $eff, .0001)));
            $soc += ($batteryW * $hours * $eff) / ($capacityKwh * 1000) * 100;
        } elseif ($netW < 0 && $soc > $minSoc) {
            $availableWh = $capacityKwh * 1000 * ($soc - $minSoc) / 100;
            $deliverable = min(-$netW, $maxDischargeW, (int) floor($availableWh * $eff / max($hours, .0001)));
            $batteryW = -$deliverable;
            $soc -= ($deliverable * $hours / max($eff, .0001)) / ($capacityKwh * 1000) * 100;
        }

        return ['power_w' => $batteryW, 'soc' => max($minSoc, min($maxSoc,$soc))];
    }
}
