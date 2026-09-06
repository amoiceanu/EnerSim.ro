<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\EquipmentComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class EquipmentFeedImporter
{
    public function __construct(private readonly XlsxWorkbookReader $reader) {}

    /** @return array{created:int, updated:int, skipped:int, by_type:array<string, int>} */
    public function import(string $path): array
    {
        $sheets = $this->reader->read($path);
        $types = ['Panouri' => 'panel', 'Invertoare' => 'inverter', 'Baterii' => 'battery'];
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'by_type' => ['panel' => 0, 'inverter' => 0, 'battery' => 0]];

        if (collect(array_keys($types))->intersect(array_keys($sheets))->isEmpty()) {
            throw new RuntimeException('Feedul nu conține foile Panouri, Invertoare sau Baterii.');
        }

        DB::transaction(function () use ($sheets, $types, &$result): void {
            foreach ($types as $sheetName => $type) {
                foreach ($sheets[$sheetName] ?? [] as $row) {
                    $payload = $this->payload($type, $row);
                    if ($payload === null) {
                        $result['skipped']++;

                        continue;
                    }

                    $component = EquipmentComponent::query()
                        ->where('sku', $payload['sku'])
                        ->orWhere('source_url', $payload['source_url'])
                        ->first();

                    if ($component) {
                        $payload['slug'] = $component->slug;
                        $payload['tech_data'] = array_replace($component->tech_data ?? [], $payload['tech_data']);
                        $component->update($payload);
                        $result['updated']++;
                    } else {
                        $payload['slug'] = $this->uniqueSlug($type, (string) $payload['sku'], (string) $payload['source_url']);
                        EquipmentComponent::create($payload);
                        $result['created']++;
                    }

                    $result['by_type'][$type]++;
                }
            }
        });

        return $result;
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function payload(string $type, array $row): ?array
    {
        $name = $this->text($row['Produs'] ?? null);
        $sku = $this->text($row['SKU'] ?? null);
        $sourceUrl = $this->text($row['URL sursă'] ?? null);
        if ($name === '' || $sku === '' || $sourceUrl === '') {
            return null;
        }

        $specifications = $this->text($row['Specificații tehnice'] ?? null);
        $description = $this->text($row['Descriere tehnică'] ?? null);
        $brand = $this->text($row['Producător'] ?? null)
            ?: $this->matchText('/Brand:\s*([^|✅]+)/iu', $specifications)
            ?: 'Necunoscut';
        $price = $this->number($row['Preț curent (RON, TVA incl.)'] ?? null);
        $checkedAt = $this->text($row['Actualizat la (UTC)'] ?? null);

        $techData = array_filter([
            'dimensions' => $this->text($row['Dimensiuni'] ?? null),
            'weight_kg' => $this->number($row['Greutate'] ?? null),
            'specifications' => $specifications,
            'description' => $description,
            'normal_price_lei' => $this->number($row['Preț normal (RON, TVA incl.)'] ?? null),
            'promo_price_lei' => $this->number($row['Preț promo (RON, TVA incl.)'] ?? null),
            'discount_percent' => ($discount = $this->number($row['Reducere'] ?? null)) !== null ? round($discount * 100, 2) : null,
            'feed_category' => $this->text($row['Categorie'] ?? null),
        ], fn ($value) => $value !== null && $value !== '');

        $techData = array_replace($techData, match ($type) {
            'panel' => $this->panelData($row, $name, $specifications, $description),
            'inverter' => $this->inverterData($row, $name, $specifications, $description),
            'battery' => $this->batteryData($row, $name, $specifications, $description),
        });

        return [
            'type' => $type,
            'brand' => Str::limit($brand, 255, ''),
            'name' => Str::limit($name, 255, ''),
            'model' => Str::limit($sku, 255, ''),
            'sku' => Str::limit($sku, 255, ''),
            'tech_data' => $techData,
            'price_lei' => $price,
            'stock_status' => $this->stockStatus($this->text($row['Stare stoc'] ?? null)),
            'source_url' => $sourceUrl,
            'source_checked_at' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkedAt) ? $checkedAt : now()->toDateString(),
            'active' => true,
        ];
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function panelData(array $row, string $name, string $specifications, string $description): array
    {
        $context = implode(' | ', [$this->text($row['Putere nominală'] ?? null), $specifications, $description, $name]);

        return [
            'power_w' => max(1, (int) round($this->powerWatts($context) ?? 1)),
            'technology' => $this->panelTechnology($context),
            'module_type' => Str::contains(Str::lower($context), 'bifacial') ? 'Bifacial' : 'Monofacial',
            'efficiency_percent' => $this->percentage($context, 'eficien') ?? 0.0,
            'voc_v' => $this->labeledNumber($context, 'tensiune circuit deschis') ?? $this->labeledNumber($context, 'voc'),
            'dimensions_mm' => $this->text($row['Dimensiuni'] ?? null),
            'ip_rating' => $this->ipRating($context) ?? 'Nespecificat',
        ];
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function inverterData(array $row, string $name, string $specifications, string $description): array
    {
        $context = implode(' | ', [$specifications, $description, $name]);
        $nominalPower = $this->labeledPower($context, 'Putere') ?? $this->powerWatts($name) ?? 1000;
        $maxPvPower = $this->labeledPower($context, 'Putere PV Max') ?? round($nominalPower * 1.3);
        $phases = Str::contains(Str::lower($context), 'trifazat') ? 3 : 1;

        return [
            'nominal_power_w' => (int) round($nominalPower),
            'max_pv_power_w' => (int) round($maxPvPower),
            'max_backup_power_w' => (int) round($nominalPower),
            'surge_power_w' => (int) round($nominalPower * 2),
            'phases' => $phases,
            'hybrid' => Str::contains(Str::lower($context), 'hibrid'),
            'mppt_count' => (int) ($this->matchNumber('/(\d+)\s*x?\s*MPPT/iu', $context) ?? 2),
            'mppt_voltage_range_v' => $this->text($row['MPPT'] ?? null) ?: $this->matchText('/Tensiune MPPT:\s*([^|✅]+)/iu', $context),
            'max_pv_voltage_v' => $this->labeledNumber($context, 'PV Max VOC'),
            'efficiency_percent' => $this->percentage($context, 'randament') ?? $this->percentage($context, 'eficien') ?? 97.0,
            'ip_rating' => $this->ipRating($context) ?? 'Nespecificat',
            'battery_supported' => Str::contains(Str::lower($context), ['hibrid', 'acumulator']),
            'dimensions_mm' => $this->text($row['Dimensiuni'] ?? null),
        ];
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function batteryData(array $row, string $name, string $specifications, string $description): array
    {
        $context = implode(' | ', [$this->text($row['Capacitate stocare'] ?? null), $this->text($row['Putere nominală'] ?? null), $this->text($row['Tensiune'] ?? null), $specifications, $description, $name]);
        $capacityAh = $this->matchNumber('/(\d+(?:[\.,]\d+)?)\s*Ah\b/iu', $context);
        $voltage = $this->matchNumber('/(\d+(?:[\.,]\d+)?)\s*V\b/iu', $context) ?? 51.2;
        $capacityKwh = $this->energyKwh($context) ?? (($capacityAh ?? 10) * $voltage / 1000);
        $maxCurrent = $this->matchNumber('/(?:curent[^:]{0,30}|descărcare[^:]{0,15}):?\s*(\d+(?:[\.,]\d+)?)\s*A\b/iu', $context);
        $power = $this->powerWatts($this->text($row['Putere nominală'] ?? null))
            ?? ($maxCurrent ? $maxCurrent * $voltage : $capacityKwh * 1000);

        return [
            'chemistry' => Str::contains(Str::lower($context), 'lifepo4') ? 'LiFePO4' : 'Baterie de stocare',
            'capacity_kwh' => round(max(0.1, $capacityKwh), 3),
            'capacity_ah' => round(max(1, $capacityAh ?? ($capacityKwh * 1000 / $voltage)), 2),
            'voltage_v' => round($voltage, 2),
            'max_power_w' => max(100, (int) round($power)),
            'max_charge_current_a' => $maxCurrent,
            'max_discharge_current_a' => $maxCurrent,
            'cycles' => (int) ($this->matchNumber('/(?:ciclu[^:]*:?|>)\s*(\d{3,6})/iu', $context) ?? 4000),
            'dod_percent' => $this->percentage($context, 'dod') ?? 80,
            'ip_rating' => $this->ipRating($context) ?? 'Nespecificat',
            'dimensions_mm' => $this->text($row['Dimensiuni'] ?? null),
        ];
    }

    private function uniqueSlug(string $type, string $sku, string $url): string
    {
        $base = Str::slug($type.'-'.$sku) ?: $type.'-'.substr(sha1($url), 0, 12);
        $slug = $base;
        $suffix = 2;
        while (EquipmentComponent::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function stockStatus(string $value): string
    {
        $value = Str::lower($value);

        return match (true) {
            Str::contains($value, 'în stoc') || Str::contains($value, 'in stoc') => 'in_stock',
            Str::contains($value, 'furnizor') => 'supplier_stock',
            Str::contains($value, 'precomand') => 'preorder',
            Str::contains($value, 'restoc') => 'restocking',
            Str::contains($value, 'epuizat') => 'out_of_stock',
            default => 'unknown',
        };
    }

    private function text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = str_replace([' ', ','], ['', '.'], $this->text($value));

        return preg_match('/-?\d+(?:\.\d+)?/', $value, $matches) ? (float) $matches[0] : null;
    }

    private function powerWatts(string $value): ?float
    {
        if (! preg_match('/(\d+(?:[\.,]\d+)?)\s*(kW|W)\b/iu', $value, $matches)) {
            return null;
        }

        $power = (float) str_replace(',', '.', $matches[1]);

        return Str::lower($matches[2]) === 'kw' ? $power * 1000 : $power;
    }

    private function labeledPower(string $value, string $label): ?float
    {
        if (! preg_match('/'.preg_quote($label, '/').'\s*:?\s*(\d+(?:[\.,]\d+)?)\s*(kW|W)\b/iu', $value, $matches)) {
            return null;
        }

        $power = (float) str_replace(',', '.', $matches[1]);

        return Str::lower($matches[2]) === 'kw' ? $power * 1000 : $power;
    }

    private function labeledNumber(string $value, string $label): ?float
    {
        return $this->matchNumber('/'.preg_quote($label, '/').'\s*:?\s*~?\s*(\d+(?:[\.,]\d+)?)/iu', $value);
    }

    private function percentage(string $value, string $label): ?float
    {
        return $this->matchNumber('/'.preg_quote($label, '/').'[^\d]{0,30}(\d+(?:[\.,]\d+)?)\s*%/iu', $value);
    }

    private function energyKwh(string $value): ?float
    {
        if (($kwh = $this->matchNumber('/(\d+(?:[\.,]\d+)?)\s*kWh\b/iu', $value)) !== null) {
            return $kwh;
        }

        $wh = $this->matchNumber('/(\d+(?:[\.,]\d+)?)\s*Wh\b/iu', $value);

        return $wh !== null ? $wh / 1000 : null;
    }

    private function ipRating(string $value): ?string
    {
        return $this->matchText('/\b(IP\s*\d{2})\b/iu', $value);
    }

    private function panelTechnology(string $value): string
    {
        return match (true) {
            Str::contains(Str::lower($value), 'topcon') => 'N-Type TOPCon',
            Str::contains(Str::lower($value), 'n-type') => 'N-Type',
            Str::contains(Str::lower($value), 'monocristalin') => 'Monocristalin',
            default => 'Fotovoltaic',
        };
    }

    private function matchNumber(string $pattern, string $value): ?float
    {
        return preg_match($pattern, $value, $matches) ? (float) str_replace(',', '.', $matches[1]) : null;
    }

    private function matchText(string $pattern, string $value): string
    {
        return preg_match($pattern, $value, $matches) ? trim($matches[1]) : '';
    }
}
