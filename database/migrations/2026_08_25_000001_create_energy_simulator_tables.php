<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('country')->default('Romania');
            $t->string('city')->default('Bucharest');
            $t->decimal('latitude', 9, 6)->default(44.4268);
            $t->decimal('longitude', 9, 6)->default(26.1025);
            $t->string('timezone')->default('Europe/Bucharest');
            $t->decimal('buy_price', 8, 4)->default(1.30);
            $t->decimal('sell_price', 8, 4)->default(.45);
            $t->timestamps();
        });
        Schema::create('st_solar_panels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('system_id')->constrained()->cascadeOnDelete();
            $t->string('name')->default('PV panel');
            $t->unsignedInteger('power_w')->default(505);
            $t->string('orientation', 2)->default('S');
            $t->unsignedTinyInteger('tilt')->default(35);
            $t->decimal('losses_percent', 5, 2)->default(15);
            $t->decimal('shading_percent', 5, 2)->default(0);
            $t->boolean('enabled')->default(true);
            $t->unsignedTinyInteger('slot')->nullable();
            $t->timestamps();
        });
        Schema::create('st_inverters', function (Blueprint $t) {
            $t->id();
            $t->foreignId('system_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('nominal_power_w');
            $t->unsignedInteger('max_pv_power_w');
            $t->unsignedInteger('max_backup_power_w');
            $t->unsignedInteger('surge_power_w');
            $t->decimal('efficiency', 5, 2)->default(97.6);
            $t->boolean('hybrid')->default(true);
            $t->boolean('battery_supported')->default(true);
            $t->boolean('zero_export_supported')->default(true);
            $t->timestamps();
        });
        Schema::create('st_batteries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('system_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('chemistry')->default('LiFePO4');
            $t->decimal('voltage', 7, 2);
            $t->decimal('capacity_kwh', 8, 3);
            $t->decimal('capacity_ah', 8, 2);
            $t->unsignedInteger('max_charge_power_w');
            $t->unsignedInteger('max_discharge_power_w');
            $t->decimal('efficiency', 5, 2)->default(95);
            $t->decimal('min_soc', 5, 2)->default(10);
            $t->decimal('max_soc', 5, 2)->default(100);
            $t->decimal('current_soc', 5, 2)->default(80);
            $t->decimal('cycle_count', 12, 3)->default(0);
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
        Schema::create('consumers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('system_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('category')->default('household');
            $t->unsignedInteger('nominal_power_w');
            $t->unsignedInteger('standby_power_w')->default(0);
            $t->unsignedInteger('startup_power_w')->default(0);
            $t->unsignedInteger('startup_duration_seconds')->default(0);
            $t->boolean('enabled')->default(true);
            $t->unsignedTinyInteger('priority')->default(2);
            $t->boolean('is_essential')->default(false);
            $t->string('icon')->nullable();
            $t->text('notes')->nullable();
            $t->string('mode')->default('permanent');
            $t->json('behavior')->nullable();
            $t->timestamps();
        });
        Schema::create('consumer_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('consumer_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('day_of_week')->nullable();
            $t->time('starts_at');
            $t->time('ends_at');
            $t->timestamps();
        });
        Schema::create('simulation_scenarios', function (Blueprint $t) {
            $t->id();
            $t->foreignId('system_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('weather')->default('clear');
            $t->string('grid_mode')->default('prosumer');
            $t->json('configuration')->nullable();
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });
        Schema::create('simulation_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('simulation_scenario_id')->constrained()->cascadeOnDelete();
            $t->dateTime('started_at');
            $t->dateTime('ended_at')->nullable();
            $t->string('status')->default('running');
            $t->json('summary')->nullable();
            $t->timestamps();
        });
        Schema::create('simulation_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('simulation_run_id')->constrained()->cascadeOnDelete();
            $t->dateTime('simulated_at');
            $t->unsignedInteger('pv_w')->default(0);
            $t->unsignedInteger('load_w')->default(0);
            $t->integer('battery_w')->default(0);
            $t->integer('grid_w')->default(0);
            $t->decimal('soc', 5, 2);
            $t->string('weather');
            $t->json('consumer_data')->nullable();
            $t->timestamps();
        });
        Schema::create('simulation_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('simulation_run_id')->nullable()->constrained()->nullOnDelete();
            $t->dateTime('simulated_at');
            $t->string('level')->default('INFO');
            $t->string('event_type');
            $t->text('message');
            $t->json('context')->nullable();
            $t->timestamps();
        });
        Schema::create('weather_profiles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->decimal('min_factor', 4, 2);
            $t->decimal('max_factor', 4, 2);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_profiles');
        Schema::dropIfExists('simulation_events');
        Schema::dropIfExists('simulation_snapshots');
        Schema::dropIfExists('simulation_runs');
        Schema::dropIfExists('simulation_scenarios');
        Schema::dropIfExists('consumer_schedules');
        Schema::dropIfExists('consumers');
        Schema::dropIfExists('st_batteries');
        Schema::dropIfExists('st_inverters');
        Schema::dropIfExists('st_solar_panels');
        Schema::dropIfExists('systems');
    }
};
