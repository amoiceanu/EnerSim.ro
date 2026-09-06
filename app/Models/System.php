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
        return $this->hasMany(SolarPanel::class);
    }

    public function inverter(): HasOne
    {
        return $this->hasOne(Inverter::class);
    }

    public function battery(): HasOne
    {
        return $this->hasOne(Battery::class);
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
