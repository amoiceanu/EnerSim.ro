<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Battery;
use App\Models\Inverter;
use App\Models\SolarPanel;
use App\Services\Catalog\AdminCatalogCsvImporter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use RuntimeException;
use Illuminate\View\View;

class AdminProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->withCount('systems')
            ->latest()
            ->paginate(30);

        $summary = [
            'projects' => Project::count(),
            'today' => Project::query()->whereDate('created_at', today())->count(),
            'ip_groups' => Project::query()->whereNotNull('owner_ip_hash')->distinct('owner_ip_hash')->count('owner_ip_hash'),
        ];

        return view('admin.projects', compact('projects', 'summary'));
    }

    public function catalog(Request $request): View
    {
        $byPrice = fn ($query) => $query
            ->orderByRaw('price_lei IS NULL')
            ->orderBy('price_lei')
            ->orderBy('id');

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = in_array($request->integer('per_page'), $allowedPerPage, true) ? $request->integer('per_page') : 20;
        $tab = $request->string('tab')->value();
        $tab = in_array($tab, ['panels', 'inverters', 'batteries'], true) ? $tab : 'panels';

        $tabs = [
            'panels' => ['label' => 'Panouri solare', 'type' => 'Panou solar', 'class' => 'is-solar', 'query' => SolarPanel::query()],
            'inverters' => ['label' => 'Invertoare', 'type' => 'Invertor', 'class' => 'is-primary', 'query' => Inverter::query()],
            'batteries' => ['label' => 'Baterii', 'type' => 'Baterie', 'class' => 'is-success', 'query' => Battery::query()],
        ];

        $counts = [
            'panels' => SolarPanel::count(),
            'inverters' => Inverter::count(),
            'batteries' => Battery::count(),
        ];
        $currentTab = $tabs[$tab];
        $catalogProducts = $byPrice($currentTab['query'])->paginate($perPage)->withQueryString();

        return view('admin.catalog', compact('catalogProducts', 'allowedPerPage', 'perPage', 'tab', 'tabs', 'counts', 'currentTab'));
    }

    public function importCatalogCsv(Request $request, AdminCatalogCsvImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'tab' => ['required', 'in:panels,inverters,batteries'],
            'catalog_csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        try {
            $result = $importer->import($request->file('catalog_csv'), $validated['tab']);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.catalog', ['tab' => $validated['tab']])
                ->withErrors(['catalog_csv' => $exception->getMessage()]);
        }

        return redirect()->route('admin.catalog', ['tab' => $validated['tab']])
            ->with('catalog_import_success', "Import finalizat: {$result['created']} adăugate, {$result['updated']} actualizate, {$result['skipped']} omise.");
    }

    public function downloadCatalogTemplate(string $tab): StreamedResponse
    {
        $templates = [
            'panels' => ['brand', 'name', 'model', 'sku', 'price_lei', 'stock_status', 'source_url', 'active', 'power_w', 'efficiency_percent', 'technology'],
            'inverters' => ['brand', 'name', 'model', 'sku', 'price_lei', 'stock_status', 'source_url', 'active', 'nominal_power_w', 'max_pv_power_w', 'efficiency_percent', 'phases', 'mppt_count'],
            'batteries' => ['brand', 'name', 'model', 'sku', 'price_lei', 'stock_status', 'source_url', 'active', 'capacity_kwh', 'max_power_w', 'voltage_v', 'cycles', 'chemistry'],
        ];
        abort_unless(array_key_exists($tab, $templates), 404);

        return response()->streamDownload(function () use ($templates, $tab): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $templates[$tab]);
            fclose($output);
        }, "enersim-{$tab}-template.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
