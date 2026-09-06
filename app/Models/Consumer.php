<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumer extends Model
{
    protected $guarded = [];

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'is_essential' => 'boolean', 'behavior' => 'array'];
    }
}
