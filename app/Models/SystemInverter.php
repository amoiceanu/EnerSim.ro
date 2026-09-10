<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemInverter extends Model
{
    protected $guarded = [];

    protected $appends = ['name', 'nominal_power_w', 'max_pv_power_w', 'max_backup_power_w', 'surge_power_w', 'efficiency', 'hybrid', 'battery_supported', 'zero_export_supported'];

    public function system(): BelongsTo { return $this->belongsTo(System::class); }
    public function inverter(): BelongsTo { return $this->belongsTo(Inverter::class); }
    public function getNameAttribute(): string { return (string) $this->inverter?->name; }
    public function getNominalPowerWAttribute(): int { return (int) ($this->inverter?->tech_data['nominal_power_w'] ?? 0); }
    public function getMaxPvPowerWAttribute(): int { return (int) ($this->inverter?->tech_data['max_pv_power_w'] ?? 0); }
    public function getMaxBackupPowerWAttribute(): int { return (int) ($this->inverter?->tech_data['max_backup_power_w'] ?? 0); }
    public function getSurgePowerWAttribute(): int { return (int) ($this->inverter?->tech_data['surge_power_w'] ?? 0); }
    public function getEfficiencyAttribute(): float { return (float) ($this->inverter?->tech_data['efficiency_percent'] ?? 0); }
    public function getHybridAttribute(): bool { return (bool) ($this->inverter?->tech_data['hybrid'] ?? false); }
    public function getBatterySupportedAttribute(): bool { return (bool) ($this->inverter?->tech_data['battery_supported'] ?? $this->hybrid); }
    public function getZeroExportSupportedAttribute(): bool { return true; }
}
