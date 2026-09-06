<?php

declare(strict_types=1);

namespace App\Services\Simulation;

use App\Models\Project;

final class ProjectAssessmentService
{
    public function assess(Project $project): array
    {
        $system = $project->systems->first();
        if (! $system) {
            return ['status' => 'incomplete', 'label' => 'Configurație incompletă', 'score' => 0, 'checks' => []];
        }

        $consumers = $system->consumers;
        $inverter = $system->inverter;
        $battery = $system->battery;
        $pvW = (int) $system->panels->where('enabled', true)->sum('power_w');
        $continuousW = (int) $consumers->where('enabled', true)->sum(fn ($consumer) => $consumer->nominal_power_w * $consumer->quantity);
        $peakW = (int) $consumers->where('enabled', true)->max(fn ($consumer) => $continuousW - ($consumer->nominal_power_w * $consumer->quantity) + (max($consumer->nominal_power_w, $consumer->startup_power_w) * $consumer->quantity));
        $dailyLoadKwh = (float) $consumers->sum(fn ($consumer) => $consumer->nominal_power_w * $consumer->quantity * (float) ($consumer->behavior['hours_per_day'] ?? 4) / 1000);
        $dailySolarKwh = $pvW / 1000 * 4.2 * .85;
        $essentialW = (int) $consumers->where('is_essential', true)->sum(fn ($consumer) => $consumer->nominal_power_w * $consumer->quantity);
        $usableBatteryKwh = $battery?->enabled ? (float) $battery->capacity_kwh * (((float) $battery->max_soc - (float) $battery->min_soc) / 100) * ((float) $battery->efficiency / 100) : 0;
        $backupHours = $essentialW > 0 ? $usableBatteryKwh / ($essentialW / 1000) : 0;

        $checks = [
            ['name' => 'Putere continuă', 'ok' => $continuousW <= $inverter->nominal_power_w, 'required' => $continuousW, 'available' => (int) $inverter->nominal_power_w, 'unit' => 'W'],
            ['name' => 'Vârf de pornire', 'ok' => $peakW <= $inverter->surge_power_w, 'required' => $peakW, 'available' => (int) $inverter->surge_power_w, 'unit' => 'W'],
            ['name' => 'Energie zilnică estimată', 'ok' => $dailySolarKwh >= $dailyLoadKwh, 'required' => round($dailyLoadKwh, 1), 'available' => round($dailySolarKwh, 1), 'unit' => 'kWh'],
            ['name' => 'Ieșire backup EPS', 'ok' => $essentialW <= $inverter->max_backup_power_w, 'required' => $essentialW, 'available' => (int) $inverter->max_backup_power_w, 'unit' => 'W'],
        ];
        $passed = collect($checks)->where('ok', true)->count();
        $score = (int) round($passed / count($checks) * 100);
        $status = $passed === count($checks) ? 'adequate' : ($passed >= 2 ? 'partial' : 'insufficient');

        return [
            'status' => $status,
            'label' => match ($status) {
                'adequate' => 'Sistemul face față', 'partial' => 'Sistem la limită', default => 'Sistem insuficient'
            },
            'score' => $score,
            'checks' => $checks,
            'pv_w' => $pvW,
            'continuous_w' => $continuousW,
            'peak_w' => $peakW,
            'daily_load_kwh' => round($dailyLoadKwh, 2),
            'daily_solar_kwh' => round($dailySolarKwh, 2),
            'backup_hours' => round($backupHours, 1),
            'recommendations' => collect($checks)->where('ok', false)->map(fn ($check) => match ($check['name']) {
                'Putere continuă' => 'Alege un invertor de minimum '.ceil($continuousW / 500) * 500 .' W sau gestionează consumatorii simultani.',
                'Vârf de pornire' => 'Este necesară o putere surge mai mare pentru motoare și compresoare.',
                'Energie zilnică estimată' => 'Adaugă '.max(1, (int) ceil(($dailyLoadKwh - $dailySolarKwh) / (0.505 * 4.2 * .85))).' panouri de 505 W.',
                default => 'Mărește puterea EPS sau redu consumatorii esențiali.',
            })->values()->all(),
        ];
    }
}
