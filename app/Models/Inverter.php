<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inverter extends Model
{
    protected $table = 'st_inverters';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['hybrid' => 'boolean', 'battery_supported' => 'boolean', 'zero_export_supported' => 'boolean'];
    }
}
