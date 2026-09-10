<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBattery extends Model
{
    protected $guarded = [];

    protected $appends = ['name', 'chemistry', 'voltage', 'capacity_kwh', 'capacity_ah', 'max_charge_power_w', 'max_discharge_power_w', 'efficiency'];

    public function system(): BelongsTo { return $this->belongsTo(System::class); }
    public function battery(): BelongsTo { return $this->belongsTo(Battery::class); }
    public function getNameAttribute(): string { return (string) $this->battery?->name; }
    public function getChemistryAttribute(): string { return (string) ($this->battery?->tech_data['chemistry'] ?? ''); }
    public function getVoltageAttribute(): float { return (float) ($this->battery?->tech_data['voltage_v'] ?? 0); }
    public function getCapacityKwhAttribute(): float { return (float) ($this->battery?->tech_data['capacity_kwh'] ?? 0); }
    public function getCapacityAhAttribute(): float { return (float) ($this->battery?->tech_data['capacity_ah'] ?? 0); }
    public function getMaxChargePowerWAttribute(): int { return (int) ($this->battery?->tech_data['max_power_w'] ?? 0); }
    public function getMaxDischargePowerWAttribute(): int { return (int) ($this->battery?->tech_data['max_power_w'] ?? 0); }
    public function getEfficiencyAttribute(): float { return 95.0; }

    protected function casts(): array { return ['enabled' => 'boolean']; }
}
