<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class System extends Model
{
    protected $guarded = [];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function panels(): HasMany
    {
        return $this->hasMany(SystemSolarPanel::class)->with('solarPanel');
    }

    public function inverter(): HasOne
    {
        return $this->hasOne(SystemInverter::class)->with('inverter');
    }

    public function battery(): HasOne
    {
        return $this->hasOne(SystemBattery::class)->with('battery');
    }

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(SimulationScenario::class);
    }
}
