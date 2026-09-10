<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inverter extends Model
{
    protected $table = 'st_inverters';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tech_data' => 'array', 'price_lei' => 'decimal:2', 'source_checked_at' => 'date', 'active' => 'boolean'];
    }
}
