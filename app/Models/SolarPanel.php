<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class SolarPanel extends Model
{
    protected $table = 'st_solar_panels';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tech_data' => 'array', 'price_lei' => 'decimal:2', 'source_checked_at' => 'date', 'active' => 'boolean'];
    }
}
