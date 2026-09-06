<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Catalog\EquipmentFeedImporter;
use Illuminate\Console\Command;

class ImportEquipmentFeed extends Command
{
    protected $signature = 'catalog:import {file : Calea către feedul Excel}';

    protected $description = 'Importă sau actualizează catalogul de echipamente dintr-un feed Excel';

    public function handle(EquipmentFeedImporter $importer): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $this->error('Fișierul nu există: '.$path);

            return self::FAILURE;
        }

        $result = $importer->import($path);
        $this->table(['Tip', 'Produse'], [
            ['Panouri', $result['by_type']['panel']],
            ['Invertoare', $result['by_type']['inverter']],
            ['Baterii', $result['by_type']['battery']],
        ]);
        $this->info("Import finalizat: {$result['created']} adăugate, {$result['updated']} actualizate, {$result['skipped']} omise.");

        return self::SUCCESS;
    }
}
