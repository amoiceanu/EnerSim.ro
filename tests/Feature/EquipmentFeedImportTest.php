<?php

namespace Tests\Feature;

use App\Models\SolarPanel;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class EquipmentFeedImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_feed_updates_products_by_sku_without_duplicates(): void
    {
        $this->seed();
        $project = Project::firstOrFail();
        $initialCount = SolarPanel::count();
        $path = $this->feedFile();

        try {
            $file = new UploadedFile(
                $path,
                'catalog.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            );

            $this->post(route('feed.import', $project), ['feed' => $file])
                ->assertRedirect(route('projects.show', $project).'#feed')
                ->assertSessionHas('feed_import_result.updated', 1);

            $this->assertDatabaseCount('st_solar_panels', $initialCount);
            $this->assertDatabaseHas('st_solar_panels', [
                'sku' => 'TSM-445-NEG9R.27',
                'price_lei' => 499,
                'stock_status' => 'in_stock',
            ]);
        } finally {
            @unlink($path);
        }
    }

    private function feedFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'enersim-feed-').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheets><x:sheet name="Panouri" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></x:sheets></x:workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>
XML);
        $zip->addFromString('xl/worksheets/sheet1.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheetData>
<x:row r="4"><x:c r="B4" t="inlineStr"><x:is><x:t>Produs</x:t></x:is></x:c><x:c r="C4" t="inlineStr"><x:is><x:t>SKU</x:t></x:is></x:c><x:c r="D4" t="inlineStr"><x:is><x:t>Producător</x:t></x:is></x:c><x:c r="E4" t="inlineStr"><x:is><x:t>Preț curent (RON, TVA incl.)</x:t></x:is></x:c><x:c r="I4" t="inlineStr"><x:is><x:t>Stare stoc</x:t></x:is></x:c><x:c r="K4" t="inlineStr"><x:is><x:t>Putere nominală</x:t></x:is></x:c><x:c r="R4" t="inlineStr"><x:is><x:t>Specificații tehnice</x:t></x:is></x:c><x:c r="T4" t="inlineStr"><x:is><x:t>URL sursă</x:t></x:is></x:c><x:c r="U4" t="inlineStr"><x:is><x:t>Actualizat la (UTC)</x:t></x:is></x:c></x:row>
<x:row r="5"><x:c r="B5" t="inlineStr"><x:is><x:t>Trina Vertex S+ 445W Bifacial</x:t></x:is></x:c><x:c r="C5" t="inlineStr"><x:is><x:t>TSM-445-NEG9R.27</x:t></x:is></x:c><x:c r="D5" t="inlineStr"><x:is><x:t>Trina Solar</x:t></x:is></x:c><x:c r="E5"><x:v>499</x:v></x:c><x:c r="I5" t="inlineStr"><x:is><x:t>În stoc</x:t></x:is></x:c><x:c r="K5" t="inlineStr"><x:is><x:t>445W</x:t></x:is></x:c><x:c r="R5" t="inlineStr"><x:is><x:t>Putere panou: 445W | Brand: Trina Solar</x:t></x:is></x:c><x:c r="T5" t="inlineStr"><x:is><x:t>https://solartech.ro/panou-fotovoltaic-445w-n-type-i-topcon-bifacial-trina-solar/</x:t></x:is></x:c><x:c r="U5" t="inlineStr"><x:is><x:t>2026-08-29</x:t></x:is></x:c></x:row>
</x:sheetData></x:worksheet>
XML);
        $zip->close();

        return $path;
    }
}
