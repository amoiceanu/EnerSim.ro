<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Battery;
use App\Models\Inverter;
use App\Models\SolarPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_catalog_is_restricted_and_lists_products_in_price_order(): void
    {
        $this->get(route('admin.catalog'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.catalog'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        SolarPanel::create(['slug' => 'panel-expensive', 'brand' => 'Brand', 'name' => 'Panou scump', 'sku' => 'EXP', 'tech_data' => ['power_w' => 500], 'price_lei' => 800, 'active' => true]);
        SolarPanel::create(['slug' => 'panel-cheap', 'brand' => 'Brand', 'name' => 'Panou ieftin', 'sku' => 'CHEAP', 'tech_data' => ['power_w' => 400], 'price_lei' => 400, 'active' => true]);
        SolarPanel::create(['slug' => 'panel-no-price', 'brand' => 'Brand', 'name' => 'Panou fără preț', 'sku' => 'NOPRICE', 'tech_data' => ['power_w' => 300], 'price_lei' => null, 'active' => false]);

        $this->get(route('admin.catalog', ['tab' => 'panels', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Panou ieftin')
            ->assertSee('Panou scump')
            ->assertSee('Panou fără preț')
            ->assertSee('Panouri solare')
            ->assertSee('Invertoare')
            ->assertSee('Baterii')
            ->assertSee('Afișare')
            ->assertSee('per_page');

        $this->get(route('admin.catalog', ['tab' => 'not-a-tab', 'per_page' => 11]))
            ->assertOk()
            ->assertSee('Panouri solare');
    }

    public function test_admin_can_download_csv_templates_for_each_catalog_tab(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach (['panels', 'inverters', 'batteries'] as $tab) {
            $response = $this->get(route('admin.catalog.template', $tab))->assertOk();
            $this->assertStringContainsString("enersim-{$tab}-template.csv", $response->headers->get('content-disposition'));
            $this->assertNotEmpty($response->streamedContent());
        }

        $this->get(route('admin.catalog.template', 'invalid'))->assertNotFound();
    }

    public function test_csv_import_creates_updates_and_skips_catalog_rows(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        SolarPanel::create([
            'slug' => 'existing-panel', 'brand' => 'Old Brand', 'name' => 'Old name', 'sku' => 'SKU-1',
            'tech_data' => ['power_w' => 300, 'obsolete' => true], 'price_lei' => 500, 'active' => true,
        ]);

        $csv = implode("\n", [
            'brand,name,model,sku,price_lei,stock_status,source_url,active,power_w,efficiency_percent,technology',
            'New Brand,Updated panel,Model 1,SKU-1,420,in_stock,https://example.test/panel,1,450,22.5,TOPCon',
            'Fresh Brand,New panel,Model 2,SKU-2,390,in_stock,https://example.test/new,1,430,21.8,N-Type',
            ',,,,,,,,,,',
        ]);

        $this->post(route('admin.catalog.import'), [
            'tab' => 'panels',
            'catalog_csv' => UploadedFile::fake()->createWithContent('panels.csv', $csv),
        ])->assertRedirect(route('admin.catalog', ['tab' => 'panels']))
            ->assertSessionHas('catalog_import_success', 'Import finalizat: 1 adăugate, 1 actualizate, 1 omise.');

        $this->assertDatabaseCount('st_solar_panels', 2);
        $updated = SolarPanel::where('sku', 'SKU-1')->firstOrFail();
        $this->assertSame('New Brand', $updated->brand);
        $this->assertSame('Updated panel', $updated->name);
        $this->assertSame(450, $updated->tech_data['power_w']);
        $this->assertArrayNotHasKey('obsolete', $updated->tech_data);

        $this->post(route('admin.catalog.import'), [
            'tab' => 'batteries',
            'catalog_csv' => UploadedFile::fake()->createWithContent('batteries.csv', implode("\n", [
                'brand,name,model,sku,price_lei,capacity_kwh,max_power_w,voltage_v,cycles,chemistry',
                'Volt,Home Battery,B-1,BAT-1,5000,5.12,2500,51.2,6000,LiFePO4',
            ])),
        ])->assertRedirect(route('admin.catalog', ['tab' => 'batteries']));

        $this->assertDatabaseHas('st_batteries', ['sku' => 'BAT-1', 'name' => 'Home Battery']);

        $this->post(route('admin.catalog.import'), [
            'tab' => 'inverters',
            'catalog_csv' => UploadedFile::fake()->createWithContent('inverters.csv', implode("\n", [
                'brand,name,model,sku,price_lei,nominal_power_w,max_pv_power_w,efficiency_percent,phases,mppt_count',
                'Grid,Hybrid 5kW,H-5,INV-1,2000,5000,6500,97,1,2',
            ])),
        ])->assertRedirect(route('admin.catalog', ['tab' => 'inverters']));

        $this->assertDatabaseHas('st_inverters', ['sku' => 'INV-1', 'name' => 'Hybrid 5kW']);
    }

    public function test_csv_import_reports_bad_headers_and_rejects_invalid_requests(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->post(route('admin.catalog.import'), [
            'tab' => 'panels',
            'catalog_csv' => UploadedFile::fake()->createWithContent('bad.csv', "brand,sku\nAcme,1\n"),
        ])->assertRedirect(route('admin.catalog', ['tab' => 'panels']))
            ->assertSessionHasErrors('catalog_csv');

        $this->post(route('admin.catalog.import'), [
            'tab' => 'unknown',
            'catalog_csv' => UploadedFile::fake()->createWithContent('bad.csv', "name\nPanel\n"),
        ])->assertSessionHasErrors('tab');
    }
}
