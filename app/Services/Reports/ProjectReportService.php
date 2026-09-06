<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\SimulationState;
use App\Models\EquipmentComponent;
use App\Models\Project;
use App\Services\Simulation\SimulationEngine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ProjectReportService
{
    public function __construct(private readonly SimulationEngine $simulation) {}

    /** @return array<string, array<string, string>> */
    public static function definitions(): array
    {
        return [
            'system-cost' => ['badge' => 'Financiar', 'icon' => '₿', 'title' => 'Costul sistemului', 'description' => 'Deviz orientativ pentru echipamentele și materialele sistemului.'],
            'daily-balance' => ['badge' => 'Zilnic', 'icon' => '▤', 'title' => 'Bilanț energetic zilnic', 'description' => 'Producția și consumul estimate pentru o zi medie.'],
            'monthly-estimate' => ['badge' => 'Estimare', 'icon' => '▦', 'title' => 'Estimare lunară', 'description' => 'Proiecție pe 30 de zile pentru producție și consum.'],
            'independence' => ['badge' => 'Eficiență', 'icon' => '◎', 'title' => 'Autoconsum și independență', 'description' => 'Gradul estimat de acoperire locală a consumului.'],
            'consumers' => ['badge' => 'Consum', 'icon' => 'ϟ', 'title' => 'Analiză consumatori', 'description' => 'Inventarul consumatorilor și necesarul lor energetic.'],
            'pv-performance' => ['badge' => 'Solar', 'icon' => '☀', 'title' => 'Performanță sistem FV', 'description' => 'Configurația și capacitatea generatorului fotovoltaic.'],
            'battery-backup' => ['badge' => 'Stocare', 'icon' => '▣', 'title' => 'Baterie și backup', 'description' => 'Capacitatea de stocare și autonomia sarcinilor esențiale.'],
            'simulation-results' => ['badge' => 'Simulare anuală', 'icon' => '◫', 'title' => 'Rezultate simulare sistem și consumatori', 'description' => 'Comparație lunară la ora 14:00, combinată cu starea vremii.'],
        ];
    }

    /** @param array<string, mixed> $assessment
     * @return array<string, mixed>
     */
    public function build(Project $project, string $type, array $assessment): array
    {
        $definition = self::definitions()[$type];
        $system = $project->systems->firstOrFail();
        $specific = match ($type) {
            'system-cost' => $this->costReport($system),
            'daily-balance' => $this->dailyReport($assessment),
            'monthly-estimate' => $this->monthlyReport($assessment),
            'independence' => $this->independenceReport($assessment),
            'consumers' => $this->consumerReport($system->consumers),
            'pv-performance' => $this->panelReport($system->panels, $system->inverter),
            'battery-backup' => $this->batteryReport($system->battery, $assessment),
            'simulation-results' => $this->monthlySimulationReport($system),
        };

        return $definition + $specific + [
            'type' => $type,
            'generated_at' => now()->format('d.m.Y, H:i'),
            'system_name' => $system->name,
        ];
    }

    /** @return array<string, mixed> */
    private function costReport(object $system): array
    {
        $catalog = EquipmentComponent::query()->where('active', true)->get()->groupBy('type');
        $equipmentRows = [];
        $equipmentTotal = 0.0;
        $unpriced = 0;

        foreach ($system->panels->groupBy(fn ($panel) => $panel->name.'|'.$panel->power_w) as $panels) {
            $panel = $panels->first();
            $component = ($catalog->get('panel') ?? collect())->first(fn ($item) => str_contains($panel->name, $item->name) || (int) ($item->tech_data['power_w'] ?? 0) === (int) $panel->power_w);
            [$row,$value,$missing] = $this->costRow('Panouri', $panel->name, $panels->count(), 'buc.', $component?->price_lei, $panel->power_w.' W/panou');
            $equipmentRows[] = $row;
            $equipmentTotal += $value;
            $unpriced += $missing;
        }

        $inverterComponent = ($catalog->get('inverter') ?? collect())->first(fn ($item) => str_contains($system->inverter->name, (string) $item->model) || (int) ($item->tech_data['nominal_power_w'] ?? 0) === (int) $system->inverter->nominal_power_w);
        [$row,$value,$missing] = $this->costRow('Invertor', $system->inverter->name, 1, 'buc.', $inverterComponent?->price_lei, $this->power($system->inverter->nominal_power_w).' nominal');
        $equipmentRows[] = $row;
        $equipmentTotal += $value;
        $unpriced += $missing;

        if ($system->battery?->enabled) {
            $batteryComponent = ($catalog->get('battery') ?? collect())->first(fn ($item) => abs((float) ($item->tech_data['capacity_kwh'] ?? 0) - (float) $system->battery->capacity_kwh) < .01);
            [$row,$value,$missing] = $this->costRow('Baterie', $system->battery->name, 1, 'buc.', $batteryComponent?->price_lei, number_format((float) $system->battery->capacity_kwh, 2, ',', '.').' kWh');
            $equipmentRows[] = $row;
            $equipmentTotal += $value;
            $unpriced += $missing;
        }

        $panelCount = max(1, $system->panels->count());
        $strings = max(1, (int) ceil($panelCount / 10));
        $auxiliary = [
            ['Cabluri', 'Cablu solar DC 6 mm²', max(20, (int) ceil($panelCount * 3.5 / 5) * 5), 'm', 8.5, 'Traseu panouri–invertor'],
            ['Cabluri', 'Cablu AC cupru', 15, 'm', 34, 'Traseu invertor–tablou'],
            ['Conectori', 'Set conectori compatibili MC4', $strings * 2, 'perechi', 32, 'Conectarea stringurilor'],
            ['Protecții', 'Tablou protecții DC', 1, 'set', 950 + max(0, $strings - 1) * 180, 'Separator, SPD și siguranțe gPV'],
            ['Protecții', 'Tablou protecții AC', 1, 'set', 950, 'Disjunctor, diferențial și SPD'],
            ['Structură', 'Structură aluminiu și cleme', $panelCount, 'panouri', 260, 'Șine și elemente de fixare'],
            ['Accesorii', 'Împământare și consumabile', 1, 'lot', 900 + $panelCount * 15, 'Conductoare, etichete și accesorii'],
        ];
        $auxiliaryRows = [];
        $auxiliaryTotal = 0.0;
        foreach ($auxiliary as [$category,$name,$quantity,$unit,$unitPrice,$specification]) {
            [$row,$value] = $this->costRow($category, $name, $quantity, $unit, $unitPrice, $specification, true);
            $auxiliaryRows[] = $row;
            $auxiliaryTotal += $value;
        }

        $documentationRows = [
            ['Documentație', 'Proiect tehnic', 'Memoriu tehnic, scheme unifilare și plan de amplasament', '1 pachet', 'La ofertare', 'Separat'],
            ['Avize', 'Documentație racordare', 'Dosar pentru operatorul de distribuție și procesul de prosumator', '1 dosar', 'La ofertare', 'Separat'],
            ['Verificare', 'Verificare proiect / punere în funcțiune', 'Servicii stabilite de proiectant și instalator autorizat', '1 serviciu', 'La ofertare', 'Separat'],
        ];
        $total = $equipmentTotal + $auxiliaryTotal;

        return [
            'metrics' => [
                $this->metric('Total general estimat', $this->money($total), 'violet'),
                $this->metric('Echipamente principale', $this->money($equipmentTotal), 'solar'),
                $this->metric('Materiale auxiliare', $this->money($auxiliaryTotal), 'blue'),
                $this->metric('Poziții fără preț', (string) $unpriced, 'green'),
            ],
            'columns' => ['Categorie', 'Componentă', 'Specificație', 'Cantitate', 'Preț unitar', 'Subtotal'],
            'rows' => [],
            'sections' => [
                ['title' => 'Echipamente principale', 'aside' => $this->money($equipmentTotal), 'footer_label' => 'Total echipamente principale', 'footer_value' => $this->money($equipmentTotal), 'rows' => $equipmentRows],
                ['title' => 'Materiale auxiliare', 'aside' => $this->money($auxiliaryTotal), 'footer_label' => 'Total materiale auxiliare', 'footer_value' => $this->money($auxiliaryTotal), 'rows' => $auxiliaryRows],
                ['title' => 'Documentația proiectului', 'aside' => 'Cost separat', 'footer_label' => 'Total documentație', 'footer_value' => 'La ofertare', 'rows' => $documentationRows],
            ],
            'note' => 'Prețurile echipamentelor și materialelor sunt orientative. Documentația, manopera, transportul și avizele se contractează separat, în funcție de amplasament și operatorul de distribuție.',
            'conclusion' => 'Devizul trebuie confirmat prin măsurători la fața locului și oferte comerciale actualizate.',
            'conclusion_variant' => 'warning',
        ];
    }

    /** @param array<string, mixed> $assessment
     * @return array<string, mixed>
     */
    private function dailyReport(array $assessment): array
    {
        $solar = (float) ($assessment['daily_solar_kwh'] ?? 0);
        $load = (float) ($assessment['daily_load_kwh'] ?? 0);
        $balance = $solar - $load;

        return $this->assessmentDocument($assessment, [
            $this->metric('Producție estimată', $this->energy($solar), 'solar'),
            $this->metric('Consum estimat', $this->energy($load), 'blue'),
            $this->metric($balance >= 0 ? 'Surplus' : 'Deficit', $this->energy(abs($balance)), $balance >= 0 ? 'green' : 'red'),
            $this->metric('Acoperire solară', $load > 0 ? min(100, (int) round($solar / $load * 100)).'%' : '100%', 'violet'),
        ], 'Valorile reprezintă o zi medie și pot varia în funcție de anotimp, vreme și programul real al consumatorilor.');
    }

    /** @param array<string, mixed> $assessment
     * @return array<string, mixed>
     */
    private function monthlyReport(array $assessment): array
    {
        $solar = (float) ($assessment['daily_solar_kwh'] ?? 0) * 30;
        $load = (float) ($assessment['daily_load_kwh'] ?? 0) * 30;

        return $this->assessmentDocument($assessment, [
            $this->metric('Producție în 30 zile', $this->energy($solar), 'solar'),
            $this->metric('Consum în 30 zile', $this->energy($load), 'blue'),
            $this->metric('Balanță lunară', ($solar >= $load ? '+' : '−').$this->energy(abs($solar - $load)), $solar >= $load ? 'green' : 'red'),
            $this->metric('Putere instalată', $this->power((int) ($assessment['pv_w'] ?? 0)), 'violet'),
        ], 'Proiecția folosește 30 de zile medii și nu înlocuiește un calcul bazat pe iradierea lunară a locației.');
    }

    /** @param array<string, mixed> $assessment
     * @return array<string, mixed>
     */
    private function independenceReport(array $assessment): array
    {
        $solar = (float) ($assessment['daily_solar_kwh'] ?? 0);
        $load = (float) ($assessment['daily_load_kwh'] ?? 0);
        $coverage = $load > 0 ? min(100, (int) round($solar / $load * 100)) : 100;

        return $this->assessmentDocument($assessment, [
            $this->metric('Independență estimată', $coverage.'%', 'green'),
            $this->metric('Dependență de rețea', (100 - $coverage).'%', 'blue'),
            $this->metric('Scor dimensionare', ($assessment['score'] ?? 0).'%', 'violet'),
            $this->metric('Verdict', (string) ($assessment['label'] ?? '—'), 'solar'),
        ], 'Independența este o estimare energetică. Profilul orar și capacitatea bateriei pot modifica rezultatul real.');
    }

    /** @return array<string, mixed> */
    private function consumerReport(Collection $consumers): array
    {
        $active = $consumers->where('enabled', true);
        $daily = $active->sum(fn ($consumer) => $consumer->nominal_power_w * $consumer->quantity * (float) ($consumer->behavior['hours_per_day'] ?? 3) / 1000);
        $rows = $consumers->map(fn ($consumer) => [
            $consumer->name,
            (string) $consumer->quantity,
            $this->power((int) $consumer->nominal_power_w),
            $this->power((int) $consumer->nominal_power_w * (int) $consumer->quantity),
            number_format((float) ($consumer->behavior['hours_per_day'] ?? 3), 1, ',', '.').' h/zi',
            $consumer->enabled ? 'Activ' : 'Oprit',
        ])->all();

        return [
            'metrics' => [
                $this->metric('Tipuri selectate', (string) $consumers->count(), 'violet'),
                $this->metric('Aparate active', (string) $active->sum('quantity'), 'green'),
                $this->metric('Putere activă', $this->power((int) $active->sum(fn ($item) => $item->nominal_power_w * $item->quantity)), 'blue'),
                $this->metric('Consum zilnic', $this->energy((float) $daily), 'solar'),
            ],
            'columns' => ['Consumator', 'Cantitate', 'Putere/buc.', 'Putere totală', 'Utilizare', 'Stare'],
            'rows' => $rows,
            'note' => 'Consumul zilnic este calculat folosind durata de funcționare configurată pentru fiecare aparat.',
            'conclusion' => 'Consumatorii cu putere mare și utilizare simultană determină dimensionarea invertorului.',
            'conclusion_variant' => 'info',
        ];
    }

    /** @return array<string, mixed> */
    private function panelReport(Collection $panels, object $inverter): array
    {
        $active = $panels->where('enabled', true);
        $installed = (int) $panels->sum('power_w');
        $activeW = (int) $active->sum('power_w');
        $rows = $panels->groupBy(fn ($panel) => $panel->name.'|'.$panel->power_w.'|'.$panel->orientation.'|'.$panel->tilt)->map(function ($items) {
            $panel = $items->first();

            return [$panel->name, (string) $items->count(), $this->power((int) $panel->power_w), $panel->orientation.' / '.$panel->tilt.'°', number_format((float) $panel->losses_percent, 1, ',', '.').'%', $items->where('enabled', true)->count().' active'];
        })->values()->all();

        return [
            'metrics' => [
                $this->metric('Putere instalată', $this->power($installed), 'solar'),
                $this->metric('Putere activă', $this->power($activeW), 'green'),
                $this->metric('Panouri active', $active->count().' / '.$panels->count(), 'blue'),
                $this->metric('Limită PV invertor', $this->power((int) $inverter->max_pv_power_w), 'violet'),
            ],
            'columns' => ['Model', 'Cantitate', 'Putere unitară', 'Orientare / unghi', 'Pierderi', 'Stare'],
            'rows' => $rows,
            'note' => 'Puterea activă trebuie verificată față de tensiunile MPPT și limita maximă PV a invertorului.',
            'conclusion' => $activeW <= $inverter->max_pv_power_w ? 'Puterea activă se încadrează în limita PV declarată a invertorului.' : 'Puterea activă depășește limita PV declarată a invertorului.',
            'conclusion_variant' => $activeW <= $inverter->max_pv_power_w ? 'success' : 'danger',
        ];
    }

    /** @param array<string, mixed> $assessment
     * @return array<string, mixed>
     */
    private function batteryReport(?object $battery, array $assessment): array
    {
        $enabled = $battery?->enabled === true;
        $rows = $enabled ? [
            ['Tehnologie', $battery->chemistry, 'Tensiune nominală', number_format((float) $battery->voltage, 1, ',', '.').' V'],
            ['Capacitate', number_format((float) $battery->capacity_kwh, 2, ',', '.').' kWh', 'Capacitate electrică', number_format((float) $battery->capacity_ah, 0, ',', '.').' Ah'],
            ['Putere încărcare', $this->power((int) $battery->max_charge_power_w), 'Putere descărcare', $this->power((int) $battery->max_discharge_power_w)],
            ['SOC utilizabil', $battery->min_soc.'–'.$battery->max_soc.'%', 'Eficiență', number_format((float) $battery->efficiency, 1, ',', '.').'%'],
        ] : [['Stare', 'Baterie neinstalată', 'Recomandare', 'Adaugă o baterie compatibilă']];

        return [
            'metrics' => [
                $this->metric('Baterie instalată', $enabled ? 'Da' : 'Nu', $enabled ? 'green' : 'red'),
                $this->metric('Capacitate', $enabled ? number_format((float) $battery->capacity_kwh, 2, ',', '.').' kWh' : '—', 'violet'),
                $this->metric('Nivel curent', $enabled ? number_format((float) $battery->current_soc, 0).'%' : '—', 'solar'),
                $this->metric('Autonomie esențiale', $enabled ? number_format((float) ($assessment['backup_hours'] ?? 0), 1, ',', '.').' h' : '0 h', 'blue'),
            ],
            'columns' => ['Parametru', 'Valoare', 'Parametru', 'Valoare'],
            'rows' => $rows,
            'note' => 'Autonomia este calculată pentru consumatorii marcați esențiali și intervalul SOC utilizabil.',
            'conclusion' => $enabled ? 'Sistemul include stocare pentru autoconsum și funcționare de rezervă.' : 'Sistemul nu include în prezent o baterie activă.',
            'conclusion_variant' => $enabled ? 'success' : 'warning',
        ];
    }

    /** @return array<string, mixed> */
    private function monthlySimulationReport(object $system): array
    {
        $monthNames = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
        $monthAbbreviations = ['Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Noi', 'Dec'];
        $weatherLabels = ['clear' => 'Însorit', 'partly_cloudy' => 'Parțial înnorat', 'cloudy' => 'Înnorat', 'rain' => 'Ploaie', 'fog' => 'Ceață', 'snow' => 'Ninsoare'];
        $activePanels = $system->panels->where('enabled', true);
        $panel = $activePanels->first() ?? $system->panels->first();
        $battery = $system->battery;
        $inverter = $system->inverter;
        $activeConsumers = $system->consumers->where('enabled', true);
        $activeConsumerIds = $activeConsumers->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sections = [];
        $sunnySummary = ['production' => 0, 'load' => 0, 'covered' => 0];

        foreach ($weatherLabels as $weather => $weatherLabel) {
            $rows = [];
            $productionTotal = 0;
            $loadTotal = 0;
            $coveredMonths = 0;
            $coverageTotal = 0;
            $simulatedMonths = 0;

            foreach (range(1, 12) as $month) {
                $weatherIsApplicable = match ($weather) {
                    'snow' => in_array($month, [1, 2, 3, 11, 12], true),
                    'fog' => in_array($month, [1, 2, 3, 10, 11, 12], true),
                    default => true,
                };
                if (! $weatherIsApplicable) {
                    $rows[] = [
                        'cells' => [$monthNames[$month - 1], '15.'.$monthAbbreviations[$month - 1].'.'.now()->year.' · 14:00', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A'],
                        'class' => 'is-not-applicable',
                    ];

                    continue;
                }

                $time = CarbonImmutable::create(now()->year, $month, 15, 14, 0, 0, $system->timezone ?: 'Europe/Bucharest');
                $state = new SimulationState(
                    time: $time,
                    panelCount: $activePanels->isEmpty() ? 0 : 1,
                    panelPowerW: (int) $activePanels->sum('power_w'),
                    orientation: $panel?->orientation ?? 'S',
                    tilt: (int) ($panel?->tilt ?? 35),
                    lossesPercent: (float) ($panel?->losses_percent ?? 15),
                    shadingPercent: (float) ($panel?->shading_percent ?? 0),
                    weather: $weather,
                    batterySoc: (float) ($battery?->current_soc ?? 0),
                    batteryCapacityKwh: (float) ($battery?->capacity_kwh ?? .5),
                    batteryMinSoc: (float) ($battery?->min_soc ?? 10),
                    batteryMaxSoc: (float) ($battery?->max_soc ?? 100),
                    maxChargeW: $battery?->enabled ? (int) $battery->max_charge_power_w : 0,
                    maxDischargeW: $battery?->enabled ? (int) $battery->max_discharge_power_w : 0,
                    batteryEfficiency: (float) ($battery?->efficiency ?? 95),
                    inverterPowerW: (int) $inverter->nominal_power_w,
                    backupPowerW: (int) $inverter->max_backup_power_w,
                    gridMode: 'prosumer',
                    gridAvailable: true,
                    consumers: $system->consumers->toArray(),
                    activeConsumerIds: $activeConsumerIds,
                    tickMinutes: 1,
                );
                $result = $this->simulation->tick($state);
                $balance = $result->solarW - $result->loadW;
                $coverage = $result->loadW > 0 ? min(100, (int) round($result->solarW / $result->loadW * 100)) : 100;
                $coverageTotal += $coverage;
                $simulatedMonths++;
                $productionTotal += $result->solarW;
                $loadTotal += $result->loadW;
                $coveredMonths += $balance >= 0 ? 1 : 0;
                $rows[] = [
                    'cells' => [$monthNames[$month - 1], '15.'.$monthAbbreviations[$month - 1].'.'.now()->year.' · 14:00', $weatherLabel, $this->power($result->solarW), $this->power($result->loadW), ($balance >= 0 ? '+' : '−').$this->power(abs($balance)), $coverage.'%'],
                    'class' => $coverage === 100 ? 'is-covered' : '',
                ];
            }

            $sections[] = [
                'title' => 'Simulare cu vreme '.$weatherLabel,
                'aside' => '12 luni · 14:00',
                'rows' => $rows,
                'footer_label' => in_array($weather, ['snow', 'fog'], true) ? 'Media acoperirii pe lunile aplicabile' : 'Media acoperirii pe toate lunile',
                'footer_value' => number_format($coverageTotal / max(1, $simulatedMonths), 1, ',', '.').'%',
            ];

            if ($weather === 'clear') {
                $sunnySummary = ['production' => $productionTotal, 'load' => $loadTotal, 'covered' => $coveredMonths];
            }
        }

        return [
            'metrics' => [
                $this->metric('Producție medie la 14:00', $this->power((int) round($sunnySummary['production'] / 12)), 'solar'),
                $this->metric('Consum mediu la 14:00', $this->power((int) round($sunnySummary['load'] / 12)), 'blue'),
                $this->metric('Luni cu acoperire completă', $sunnySummary['covered'].' / 12', 'green'),
                $this->metric('Consumatori incluși', (string) $activeConsumers->sum('quantity'), 'violet'),
            ],
            'columns' => ['Luna', 'Moment', 'Starea vremii', 'Producție', 'Consum', 'Balanță', 'Acoperire'],
            'rows' => [],
            'sections' => $sections,
            'note' => 'Fiecare tabel simulează toate lunile în ziua de 15, la ora 14:00, pentru o singură stare meteo. Sunt incluși toți consumatorii activi.',
            'conclusion' => "În scenariul însorit, producția fotovoltaică acoperă integral consumul în {$sunnySummary['covered']} din 12 luni simulate.",
            'conclusion_variant' => $sunnySummary['covered'] >= 6 ? 'success' : 'warning',
        ];
    }

    /** @param array<string, mixed> $assessment
     * @param  array<int, array<string, string>>  $metrics
     * @return array<string, mixed>
     */
    private function assessmentDocument(array $assessment, array $metrics, string $note): array
    {
        return [
            'metrics' => $metrics,
            'columns' => ['Verificare', 'Necesar', 'Disponibil', 'Rezultat'],
            'rows' => collect($assessment['checks'] ?? [])->map(fn ($check) => [$check['name'], $check['required'].' '.$check['unit'], $check['available'].' '.$check['unit'], $check['ok'] ? 'Conform' : 'Necesită ajustare'])->all(),
            'note' => $note,
            'conclusion' => (string) ($assessment['label'] ?? 'Configurație incompletă').'. Scorul general de dimensionare este '.($assessment['score'] ?? 0).'%.',
            'conclusion_variant' => collect($assessment['checks'] ?? [])->every(fn ($check) => $check['ok']) ? 'success' : 'warning',
        ];
    }

    /** @return array{0:array<int, string>,1:float,2:int} */
    private function costRow(string $category, string $name, int $quantity, string $unit, mixed $unitPrice, string $specification, bool $estimated = false): array
    {
        $price = $unitPrice !== null ? (float) $unitPrice : null;
        $total = ($price ?? 0) * $quantity;

        return [[
            $category,
            $name,
            $specification.($estimated ? ' · estimat' : ''),
            $quantity.' '.$unit,
            $price === null ? 'Indisponibil' : $this->money($price),
            $price === null ? '—' : $this->money($total),
        ], $total, $price === null ? 1 : 0];
    }

    /** @return array<string, string> */
    private function metric(string $label, string $value, string $tone): array
    {
        return compact('label', 'value', 'tone');
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', '.').' lei';
    }

    private function energy(float $value): string
    {
        return number_format($value, 2, ',', '.').' kWh';
    }

    private function power(int $watts): string
    {
        return abs($watts) >= 1000 ? number_format($watts / 1000, 2, ',', '.').' kW' : $watts.' W';
    }
}
