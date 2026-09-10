<?php

namespace Database\Seeders;

use App\Models\Battery;
use App\Models\Inverter;
use App\Models\SolarPanel;
use Illuminate\Database\Seeder;

class EquipmentCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->components() as $component) {
            $model = match ($component['type']) {
                'panel' => SolarPanel::class,
                'inverter' => Inverter::class,
                'battery' => Battery::class,
            };
            unset($component['type']);
            $model::updateOrCreate(['slug' => $component['slug']], $component);
        }
    }

    private function components(): array
    {
        $checkedAt = '2026-08-28';

        return [
            $this->component('panel', 'trina-vertex-s-plus-445', 'Trina Solar', 'Trina Vertex S+ 445W Bifacial', 'TSM-NEG9RC.27', 'TSM-445-NEG9R.27', 448, 'in_stock', 'https://solartech.ro/panou-fotovoltaic-445w-n-type-i-topcon-bifacial-trina-solar/', $checkedAt, [
                'power_w' => 445, 'technology' => 'N-Type i-TOPCon monocristalin', 'module_type' => 'Bifacial Dual Glass', 'efficiency_percent' => 22.3,
                'vmpp_v' => 44.3, 'impp_a' => 10.05, 'voc_v' => 52.6, 'isc_a' => 10.71, 'max_system_voltage_v' => 1500,
                'cells' => 144, 'temperature_coefficient_pmax_percent_c' => -0.29, 'dimensions_mm' => '1762 × 1134 × 30', 'weight_kg' => 21,
                'ip_rating' => 'IP68', 'product_warranty_years' => 15, 'performance_warranty_years' => 30,
            ]),
            $this->component('panel', 'trina-vertex-s-plus-505', 'Trina Solar', 'Trina Vertex S+ 505W Bifacial', 'TSM-505-NEG18RC.27', 'TSM-505-NEG18RC.27', 505, 'in_stock', 'https://solartech.ro/panou-fotovoltaic-505w-n-type-i-topcon-rama-neagra-trina-solar/', $checkedAt, [
                'power_w' => 505, 'technology' => 'N-Type i-TOPCon monocristalin', 'module_type' => 'Bifacial Dual Glass', 'efficiency_percent' => 22.7,
                'vmpp_v' => 33.5, 'impp_a' => 15.09, 'voc_v' => 40.3, 'isc_a' => 15.89, 'max_system_voltage_v' => 1500,
                'cells' => 108, 'temperature_coefficient_pmax_percent_c' => -0.29, 'dimensions_mm' => '1961 × 1134 × 30', 'weight_kg' => 23.5,
                'ip_rating' => 'IP68', 'performance_warranty_years' => 30,
            ]),
            $this->component('panel', 'aiko-stellar-1n-645', 'AIKO', 'AIKO Stellar 1N+ 645W Bifacial', 'AIKO-G645-MCH72Dw', 'AIKO-G645MCH72Dw', 650, 'in_stock', 'https://solartech.ro/panou-fotovoltaic-645w-stellar-1n-bifacial-aiko/', $checkedAt, [
                'power_w' => 645, 'technology' => 'N-Type ABC', 'module_type' => 'Bifacial Dual Glass', 'efficiency_percent' => 23.9,
                'vmpp_v' => 45.2, 'impp_a' => 14.27, 'voc_v' => 53.9, 'isc_a' => 15.0, 'max_system_voltage_v' => 1500,
                'cells' => 144, 'dimensions_mm' => '2382 × 1134 × 30', 'weight_kg' => 32.3, 'ip_rating' => 'IP68',
                'product_warranty_years' => 15, 'performance_warranty_years' => 30, 'annual_degradation_percent' => 0.35,
            ]),
            $this->component('inverter', 'deye-sun-3-6k-sg05lp1', 'Deye', 'Deye Hibrid 3.6kW Monofazat', 'SUN-3.6K-SG05LP1-EU-AM2', 'SUN-3.6K-SG05LP1-EU-AM2', 3899, 'restocking', 'https://solartech.ro/invertor-hibrid-deye-3-6kw-48v-2xmppt-sun-3-6k-sg05lp1-eu-am2/', $checkedAt, [
                'nominal_power_w' => 3600, 'max_pv_power_w' => 4680, 'max_backup_power_w' => 3600, 'surge_power_w' => 7200,
                'phases' => 1, 'hybrid' => true, 'battery_voltage_range_v' => '40–60', 'mppt_count' => 2, 'max_pv_voltage_v' => 500,
                'communication' => ['CAN', 'RS485', 'WiFi'], 'efficiency_percent' => 97.6, 'ip_rating' => 'IP65', 'warranty_years' => 10,
            ]),
            $this->component('inverter', 'deye-sun-6k-sg05lp1', 'Deye', 'Deye Hibrid 6kW Monofazat', 'SUN-6K-SG05LP1-EU-AM2-P', 'SUN-6K-SG05LP1-EU-AM2-P', 3999, 'restocking', 'https://solartech.ro/invertor-deye-hibrid-6kw-48v-2xmppt-sun-6k-sg03lp1-eu/', $checkedAt, [
                'nominal_power_w' => 6000, 'max_pv_power_w' => 9600, 'max_backup_power_w' => 6000, 'surge_power_w' => 12000,
                'phases' => 1, 'hybrid' => true, 'battery_voltage_range_v' => '40–60', 'max_charge_current_a' => 135, 'mppt_count' => 2,
                'mppt_voltage_range_v' => '150–425', 'max_pv_voltage_v' => 500, 'communication' => ['CAN', 'RS485', 'WiFi'],
                'efficiency_percent' => 97.6, 'ip_rating' => 'IP65', 'warranty_years' => 10,
            ]),
            $this->component('inverter', 'deye-sun-10k-sg05lp3', 'Deye', 'Deye Hibrid 10kW Trifazat', 'SUN-10K-SG05LP3-EU-SM2', 'SUN-10K-SG05LP3-EU-SM2', 8949, 'restocking', 'https://solartech.ro/invertor-deye-hibrid-10kw-48v-2xmppt-sun-10k-sg05lp3-eu-sm2/', $checkedAt, [
                'nominal_power_w' => 10000, 'max_pv_power_w' => 13000, 'max_backup_power_w' => 10000, 'surge_power_w' => 20000,
                'phases' => 3, 'hybrid' => true, 'battery_voltage_range_v' => '40–60', 'max_charge_current_a' => 210, 'mppt_count' => 2,
                'mppt_voltage_range_v' => '200–650', 'max_pv_voltage_v' => 800, 'communication' => ['CAN', 'RS485', 'RS232', 'WiFi'],
                'efficiency_percent' => 97.6, 'ip_rating' => 'IP65', 'warranty_years' => 10,
            ]),
            $this->component('battery', 'vtac-vt-12040-1', 'V-TAC', 'V-TAC LiFePO4 10.24kWh IP65', 'VT-12040-1', '12766', 7643, 'in_stock', 'https://solartech.ro/acumulator-lifepo4-10-24kwh-vt-12040-1-alb-ip65-v-tac/', $checkedAt, [
                'chemistry' => 'LiFePO4', 'capacity_kwh' => 10.24, 'capacity_ah' => 200, 'voltage_v' => 51.2, 'max_power_w' => 5120,
                'max_charge_current_a' => 100, 'max_discharge_current_a' => 100, 'cycles' => 6000, 'dod_percent' => 90,
                'communication' => ['CAN'], 'ip_rating' => 'IP65', 'dimensions_mm' => '460 × 265 × 800', 'weight_kg' => 95.8, 'warranty_years' => 10,
            ]),
            $this->component('battery', 'pomega-pbl-51100', 'Pomega', 'Pomega PBL-51100 LiFePO4 5.12kWh', 'PBL-51100', 'PBL-51100', 7319.99, 'supplier_stock', 'https://solartech.ro/acumulator-fotovoltaice-lifepo4-5-12kwh-pbl-51100-pomega/', $checkedAt, [
                'chemistry' => 'LiFePO4', 'capacity_kwh' => 5.12, 'capacity_ah' => 100, 'voltage_v' => 51.2, 'max_power_w' => 5120,
                'max_charge_current_a' => 100, 'max_discharge_current_a' => 100, 'cycles' => 6000, 'dod_percent' => 80,
                'communication' => ['CAN', 'RS485'], 'ip_rating' => 'IP20', 'parallel_units' => 8,
                'dimensions_mm' => '446 × 532 × 160', 'weight_kg' => 50, 'warranty_years' => 10,
            ]),
            $this->component('battery', 'vtac-vt-16076b', 'V-TAC', 'V-TAC LiFePO4 16.07kWh', 'VT-16076B', '12335', 9899, 'in_stock', 'https://solartech.ro/acumulator-fotovoltaice-lidepo4-16-07kwh-vt-16076b-v-tac/', $checkedAt, [
                'chemistry' => 'LiFePO4', 'capacity_kwh' => 16.07, 'capacity_ah' => 314, 'voltage_v' => 51.2, 'max_power_w' => 7680,
                'max_charge_current_a' => 150, 'max_discharge_current_a' => 150, 'cycles' => 7000, 'communication' => ['CAN'],
                'ip_rating' => 'IP20', 'parallel_units' => 16, 'dimensions_mm' => '510 × 242 × 862', 'weight_kg' => 135, 'warranty_years' => 10,
            ]),
            $this->component('battery', 'deye-se-f16-max', 'Deye', 'Deye SE-F16 Max-6 16kWh', 'SE-F16 Max-6', 'SE-F16-MAX', 10769, 'in_stock', 'https://solartech.ro/acumulator-lifepo4-16kwh-se-f16-max-cu-incalzire-deye/', $checkedAt, [
                'chemistry' => 'LiFePO4', 'capacity_kwh' => 16.0, 'usable_capacity_kwh' => 16.0, 'capacity_ah' => 314, 'voltage_v' => 51.2,
                'max_power_w' => 11800, 'max_charge_current_a' => 160, 'max_discharge_current_a' => 200, 'cycles' => 6000, 'dod_percent' => 90,
                'communication' => ['CAN 2.0', 'RS485', 'Bluetooth'], 'ip_rating' => 'IP65 / NEMA 3R', 'parallel_units' => 32,
                'heating' => true, 'dimensions_mm' => '464 × 914 × 244.5', 'weight_kg' => 120, 'warranty_years' => 10,
            ]),
        ];
    }

    private function component(string $type, string $slug, string $brand, string $name, string $model, string $sku, float $price, string $stock, string $url, string $checkedAt, array $techData): array
    {
        return [
            'type' => $type, 'slug' => $slug, 'brand' => $brand, 'name' => $name, 'model' => $model, 'sku' => $sku,
            'tech_data' => $techData, 'price_lei' => $price, 'stock_status' => $stock, 'source_url' => $url,
            'source_checked_at' => $checkedAt, 'active' => true,
        ];
    }
}
