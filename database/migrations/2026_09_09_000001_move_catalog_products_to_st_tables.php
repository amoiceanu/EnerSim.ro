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
        Schema::rename('st_solar_panels', 'legacy_system_solar_panels');
        Schema::rename('st_inverters', 'legacy_system_inverters');
        Schema::rename('st_batteries', 'legacy_system_batteries');

        foreach (['st_solar_panels', 'st_inverters', 'st_batteries'] as $table) {
            Schema::create($table, function (Blueprint $t): void {
                $t->id();
                $t->string('slug')->unique();
                $t->string('brand')->default('EnerSim');
                $t->string('name');
                $t->string('model')->nullable();
                $t->string('sku')->nullable()->index();
                $t->json('tech_data');
                $t->decimal('price_lei', 12, 2)->nullable();
                $t->string('stock_status', 32)->nullable();
                $t->text('source_url')->nullable();
                $t->date('source_checked_at')->nullable();
                $t->boolean('active')->default(true);
                $t->timestamps();
            });
        }

        Schema::create('system_solar_panels', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('system_id')->constrained()->cascadeOnDelete();
            $t->foreignId('solar_panel_id')->constrained('st_solar_panels')->restrictOnDelete();
            $t->string('orientation', 2)->default('S');
            $t->unsignedTinyInteger('tilt')->default(35);
            $t->decimal('losses_percent', 5, 2)->default(15);
            $t->decimal('shading_percent', 5, 2)->default(0);
            $t->boolean('enabled')->default(true);
            $t->unsignedTinyInteger('slot')->nullable();
            $t->timestamps();
            $t->unique(['system_id', 'slot']);
        });
        Schema::create('system_inverters', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('system_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('inverter_id')->constrained('st_inverters')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('system_batteries', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('system_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('battery_id')->constrained('st_batteries')->restrictOnDelete();
            $t->decimal('min_soc', 5, 2)->default(10);
            $t->decimal('max_soc', 5, 2)->default(100);
            $t->decimal('current_soc', 5, 2)->default(80);
            $t->decimal('cycle_count', 12, 3)->default(0);
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });

        $this->copyImportedProducts();
        $this->migrateProjectConfiguration();

        Schema::drop('legacy_system_solar_panels');
        Schema::drop('legacy_system_inverters');
        Schema::drop('legacy_system_batteries');
    }

    private function copyImportedProducts(): void
    {
        foreach (['panel' => 'st_solar_panels', 'inverter' => 'st_inverters', 'battery' => 'st_batteries'] as $type => $target) {
            foreach (DB::table('equipment_components')->where('type', $type)->get() as $product) {
                DB::table($target)->insert([
                    'slug' => $product->slug, 'brand' => $product->brand, 'name' => $product->name,
                    'model' => $product->model, 'sku' => $product->sku, 'tech_data' => $product->tech_data,
                    'price_lei' => $product->price_lei, 'stock_status' => $product->stock_status,
                    'source_url' => $product->source_url, 'source_checked_at' => $product->source_checked_at,
                    'active' => $product->active, 'created_at' => $product->created_at, 'updated_at' => $product->updated_at,
                ]);
            }
        }
    }

    private function migrateProjectConfiguration(): void
    {
        foreach (DB::table('legacy_system_solar_panels')->orderBy('id')->get() as $legacy) {
            $productId = $this->catalogId('st_solar_panels', 'power_w', (int) $legacy->power_w)
                ?? $this->legacyProduct('st_solar_panels', 'panel', $legacy->name, ['power_w' => (int) $legacy->power_w]);
            DB::table('system_solar_panels')->insert([
                'system_id' => $legacy->system_id, 'solar_panel_id' => $productId, 'orientation' => $legacy->orientation,
                'tilt' => $legacy->tilt, 'losses_percent' => $legacy->losses_percent, 'shading_percent' => $legacy->shading_percent,
                'enabled' => $legacy->enabled, 'slot' => $legacy->slot, 'created_at' => $legacy->created_at, 'updated_at' => $legacy->updated_at,
            ]);
        }

        foreach (DB::table('legacy_system_inverters')->orderBy('id')->get() as $legacy) {
            $productId = $this->catalogId('st_inverters', 'nominal_power_w', (int) $legacy->nominal_power_w)
                ?? $this->legacyProduct('st_inverters', 'inverter', $legacy->name, [
                    'nominal_power_w' => (int) $legacy->nominal_power_w, 'max_pv_power_w' => (int) $legacy->max_pv_power_w,
                    'max_backup_power_w' => (int) $legacy->max_backup_power_w, 'surge_power_w' => (int) $legacy->surge_power_w,
                    'efficiency_percent' => (float) $legacy->efficiency, 'hybrid' => (bool) $legacy->hybrid,
                    'battery_supported' => (bool) $legacy->battery_supported,
                ]);
            DB::table('system_inverters')->insert(['system_id' => $legacy->system_id, 'inverter_id' => $productId, 'created_at' => $legacy->created_at, 'updated_at' => $legacy->updated_at]);
        }

        foreach (DB::table('legacy_system_batteries')->orderBy('id')->get() as $legacy) {
            $productId = $this->catalogId('st_batteries', 'capacity_kwh', (float) $legacy->capacity_kwh)
                ?? $this->legacyProduct('st_batteries', 'battery', $legacy->name, [
                    'chemistry' => $legacy->chemistry, 'voltage_v' => (float) $legacy->voltage,
                    'capacity_kwh' => (float) $legacy->capacity_kwh, 'capacity_ah' => (float) $legacy->capacity_ah,
                    'max_power_w' => max((int) $legacy->max_charge_power_w, (int) $legacy->max_discharge_power_w),
                ]);
            DB::table('system_batteries')->insert([
                'system_id' => $legacy->system_id, 'battery_id' => $productId, 'min_soc' => $legacy->min_soc,
                'max_soc' => $legacy->max_soc, 'current_soc' => $legacy->current_soc, 'cycle_count' => $legacy->cycle_count,
                'enabled' => $legacy->enabled, 'created_at' => $legacy->created_at, 'updated_at' => $legacy->updated_at,
            ]);
        }
    }

    private function catalogId(string $table, string $key, int|float $value): ?int
    {
        foreach (DB::table($table)->select(['id', 'tech_data'])->get() as $product) {
            $data = json_decode($product->tech_data, true) ?: [];
            if ((float) ($data[$key] ?? -1) === (float) $value) return $product->id;
        }

        return null;
    }

    private function legacyProduct(string $table, string $type, string $name, array $techData): int
    {
        return DB::table($table)->insertGetId([
            'slug' => 'legacy-'.Str::slug($type.'-'.$name).'-'.Str::random(8), 'brand' => 'Configurație existentă',
            'name' => $name, 'tech_data' => json_encode($techData), 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_batteries');
        Schema::dropIfExists('system_inverters');
        Schema::dropIfExists('system_solar_panels');
        Schema::dropIfExists('st_batteries');
        Schema::dropIfExists('st_inverters');
        Schema::dropIfExists('st_solar_panels');
    }
};
