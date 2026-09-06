<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPanel extends Model
{
    protected $table = 'st_solar_panels';

    protected $guarded = [];

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
