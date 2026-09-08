<?php

namespace Database\Seeders;

use App\Models\Battery;
use App\Models\Consumer;
use App\Models\Inverter;
use App\Models\Project;
use App\Models\SimulationScenario;
use App\Models\SolarPanel;
use App\Models\System;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(EquipmentCatalogSeeder::class);

        $project = Project::create(['name' => 'Casa București', 'share_token' => (string) Str::uuid(), 'county' => 'București', 'city' => 'București', 'latitude' => 44.4268, 'longitude' => 26.1025]);
        $system = System::create(['project_id' => $project->id, 'name' => 'Home Hybrid', 'city' => 'Bucharest']);
        foreach (range(1, 4) as $slot) {
            SolarPanel::create(['system_id' => $system->id, 'name' => 'Panou '.$slot, 'power_w' => 505, 'orientation' => 'S', 'tilt' => 35, 'slot' => $slot]);
        }
        Inverter::create(['system_id' => $system->id, 'name' => 'Deye SUN-3.6K-SG05LP1-EU-AM2', 'nominal_power_w' => 3600, 'max_pv_power_w' => 4680, 'max_backup_power_w' => 3600, 'surge_power_w' => 7200]);
        Battery::create(['system_id' => $system->id, 'name' => 'LiFePO4 51,2 V / 5,12 kWh', 'voltage' => 51.2, 'capacity_kwh' => 5.12, 'capacity_ah' => 100, 'max_charge_power_w' => 2500, 'max_discharge_power_w' => 2500, 'current_soc' => 80]);
        $consumers = [
            ['Centrală pe lemne', 150, 0, 'permanent', 1], ['Pompă centrală 1', 70, 0, 'permanent', 1], ['Pompă centrală 2', 70, 0, 'permanent', 1], ['UPS', 40, 0, 'permanent', 1],
            ['Frigider', 120, 700, 'cycle', 1], ['Ladă frigorifică', 120, 700, 'cycle', 1], ['Hidrofor', 900, 2500, 'random', 1], ['Router', 12, 0, 'permanent', 2], ['Iluminat', 80, 0, 'manual', 3], ['Mașină de spălat', 1800, 2100, 'manual', 3], ['Boiler', 2000, 0, 'manual', 3], ['TV', 90, 0, 'manual', 3], ['PC', 240, 450, 'manual', 3],
        ];
        foreach ($consumers as [$name,$power,$startup,$mode,$priority]) {
            Consumer::create(['system_id' => $system->id, 'name' => $name, 'nominal_power_w' => $power, 'startup_power_w' => $startup, 'startup_duration_seconds' => $startup ? 2 : 0, 'mode' => $mode, 'priority' => $priority, 'is_essential' => $priority === 1, 'behavior' => ($mode === 'cycle' ? ['run_minutes' => 15, 'cycle_minutes' => 45] : ($mode === 'random' ? ['times_per_day' => 10] : [])) + ['hours_per_day' => $mode === 'permanent' ? 24 : 3]]);
        }
        SimulationScenario::create(['system_id' => $system->id, 'name' => 'Home Hybrid 4 Panel', 'weather' => 'clear', 'grid_mode' => 'prosumer', 'is_default' => true, 'configuration' => ['slots' => 6]]);
        foreach ([['Senin', 'clear', .95, 1], ['Parțial noros', 'partly_cloudy', .7, .9], ['Noros', 'cloudy', .3, .6], ['Ploaie', 'rain', .2, .4], ['Ceață', 'fog', .2, .5], ['Zăpadă', 'snow', .05, .3]] as $w) {
            DB::table('weather_profiles')->insert(['name' => $w[0], 'code' => $w[1], 'min_factor' => $w[2], 'max_factor' => $w[3], 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
