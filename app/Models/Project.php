<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            $project->share_token ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'share_token';
    }

    public function systems(): HasMany
    {
        return $this->hasMany(System::class);
    }
}
