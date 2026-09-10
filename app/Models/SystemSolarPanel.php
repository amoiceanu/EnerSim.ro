<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSolarPanel extends Model
{
    protected $guarded = [];

    protected $appends = ['name', 'power_w'];

    public function system(): BelongsTo { return $this->belongsTo(System::class); }
    public function solarPanel(): BelongsTo { return $this->belongsTo(SolarPanel::class); }
    public function getNameAttribute(): string { return (string) $this->solarPanel?->name; }
    public function getPowerWAttribute(): int { return (int) ($this->solarPanel?->tech_data['power_w'] ?? 0); }

    protected function casts(): array { return ['enabled' => 'boolean']; }
}
