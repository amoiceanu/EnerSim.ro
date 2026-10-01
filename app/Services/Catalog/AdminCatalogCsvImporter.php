<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Battery;
use App\Models\Inverter;
use App\Models\SolarPanel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

final class AdminCatalogCsvImporter
{
    /** @return array{created:int, updated:int, skipped:int} */
    public function import(UploadedFile $file, string $type): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('Fișierul CSV nu a putut fi citit.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            throw new RuntimeException('Fișierul CSV este gol.');
        }

        $header = array_map(fn ($value) => Str::lower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))), $header);
        if (! in_array('name', $header, true)) {
            throw new RuntimeException('Template-ul CSV trebuie să conțină coloana obligatorie „name”.');
        }

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $model = $this->model($type);
        while (($values = fgetcsv($handle)) !== false) {
            $row = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), null));
            if (! is_array($row) || trim((string) ($row['name'] ?? '')) === '') {
                $result['skipped']++;
                continue;
            }

            $payload = $this->payload($type, $row);
            $existing = ! empty($payload['sku'])
                ? $model::query()->where('sku', $payload['sku'])->first()
                : $model::query()->where('name', $payload['name'])->where('brand', $payload['brand'])->first();

            if ($existing) {
                // A CSV import is authoritative: do not retain old specifications
                // when a matching SKU (or name + brand) already exists.
                $existing->update($payload);
                $result['updated']++;
            } else {
                $payload['slug'] = $this->uniqueSlug($type, $payload['brand'].'-'.$payload['name']);
                $model::create($payload);
                $result['created']++;
            }
        }

        fclose($handle);

        return $result;
    }

    /** @param array<string, string|null> $row
     * @return array<string, mixed>
     */
    private function payload(string $type, array $row): array
    {
        $techKeys = match ($type) {
            'panels' => ['power_w', 'efficiency_percent', 'technology'],
            'inverters' => ['nominal_power_w', 'max_pv_power_w', 'efficiency_percent', 'phases', 'mppt_count'],
            'batteries' => ['capacity_kwh', 'max_power_w', 'voltage_v', 'cycles', 'chemistry'],
        };

        $techData = [];
        foreach ($techKeys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                $techData[$key] = in_array($key, ['technology', 'chemistry'], true)
                    ? trim((string) $row[$key])
                    : $this->number((string) $row[$key]);
            }
        }

        return [
            'brand' => trim((string) ($row['brand'] ?? '')) ?: 'EnerSim',
            'name' => trim((string) $row['name']),
            'model' => $this->nullableText($row['model'] ?? null),
            'sku' => $this->nullableText($row['sku'] ?? null),
            'price_lei' => $this->number($row['price_lei'] ?? null),
            'stock_status' => $this->nullableText($row['stock_status'] ?? null),
            'source_url' => $this->nullableText($row['source_url'] ?? null),
            'source_checked_at' => now()->toDateString(),
            'active' => ! in_array(Str::lower(trim((string) ($row['active'] ?? '1'))), ['0', 'false', 'nu', 'inactiv'], true),
            'tech_data' => $techData,
        ];
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function number(?string $value): ?float
    {
        $value = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return preg_match('/-?\d+(?:\.\d+)?/', $value, $matches) ? (float) $matches[0] : null;
    }

    /** @return class-string<SolarPanel|Inverter|Battery> */
    private function model(string $type): string
    {
        return match ($type) {
            'panels' => SolarPanel::class,
            'inverters' => Inverter::class,
            'batteries' => Battery::class,
            default => throw new RuntimeException('Tip de componentă invalid.'),
        };
    }

    private function uniqueSlug(string $type, string $name): string
    {
        $model = $this->model($type);
        $base = Str::slug($type.'-'.$name) ?: $type.'-component';
        $slug = $base;
        $suffix = 2;

        while ($model::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
