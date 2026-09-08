<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('share_token')->unique();
            $table->string('name');
            $table->string('county');
            $table->string('city');
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('timezone')->default('Europe/Bucharest');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('systems', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        if (DB::table('systems')->exists()) {
            $system = DB::table('systems')->first();
            if ($system) {
                $projectId = DB::table('projects')->insertGetId([
                    'name' => $system->name,
                    'share_token' => (string) Str::uuid(),
                    'county' => 'București',
                    'city' => $system->city,
                    'latitude' => $system->latitude,
                    'longitude' => $system->longitude,
                    'timezone' => $system->timezone,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('systems')->update(['project_id' => $projectId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('systems', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
        Schema::dropIfExists('projects');
    }
};
