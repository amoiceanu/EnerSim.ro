@extends('layouts.app', ['title' => 'Catalog componente · EnerSim'])

@section('content')
<div class="admin-dashboard-shell">
    <div class="admin-dashboard-nav"><x-public-nav /></div>

    <main class="admin-catalog-page">
        <header class="admin-catalog-head">
            <div>
                <p>ADMINISTRARE · CATALOG</p>
                <h1>Componente importate</h1>
                <span>Produse importate din catalogul EnerSim, ordonate după preț crescător în fiecare categorie.</span>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="admin-projects-back">← Proiecte publice</a>
        </header>

        <div class="admin-catalog-tabs" role="tablist" aria-label="Tip componentă">
            @foreach($tabs as $tabKey => $tabDefinition)
                <a href="{{ route('admin.catalog', ['tab' => $tabKey, 'per_page' => $perPage]) }}" role="tab" aria-selected="{{ $tab === $tabKey ? 'true' : 'false' }}" class="{{ $tab === $tabKey ? 'is-active' : '' }}">{{ $tabDefinition['label'] }} <span>{{ $counts[$tabKey] }}</span></a>
            @endforeach
        </div>

        @if(session('catalog_import_success'))
            <p class="admin-catalog-import-feedback is-success" role="status">{{ session('catalog_import_success') }}</p>
        @endif
        @error('catalog_csv')
            <p class="admin-catalog-import-feedback is-error" role="alert">{{ $message }}</p>
        @enderror

        <section class="admin-catalog-table" aria-labelledby="catalog-table-title">
            <div class="admin-catalog-table-head">
                <div><p>REGISTRU CATALOG</p><h2 id="catalog-table-title">{{ $currentTab['label'] }}</h2></div>
                <div class="admin-catalog-table-actions" x-data="{ fileName: '' }">
                    <span>{{ $catalogProducts->total() }} componente · preț crescător</span>
                    <a href="{{ route('admin.catalog.template', $tab) }}" class="admin-catalog-template">⇩ Template CSV</a>
                    <form method="POST" action="{{ route('admin.catalog.import') }}" enctype="multipart/form-data" class="admin-catalog-import-form">
                        @csrf
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <label class="admin-catalog-file" :class="{ 'has-file': fileName }">
                            <span x-text="fileName || 'Alege CSV'"></span>
                            <input type="file" name="catalog_csv" accept=".csv,text/csv" required @change="fileName = $event.target.files[0]?.name || ''">
                        </label>
                        <button type="submit">⇧ Importă CSV</button>
                    </form>
                </div>
            </div>
            <div class="admin-catalog-scroll">
                <table>
                    <thead><tr><th>#</th><th>ID</th><th>Tip</th><th>Brand / produs</th><th>Model</th><th>Specificații</th><th>Preț</th><th>Stare</th></tr></thead>
                    <tbody>
                        @forelse($catalogProducts as $index => $product)
                            <tr>
                                <td class="admin-catalog-row-number">{{ $catalogProducts->firstItem() + $index }}</td>
                                <td><code>#{{ $product->id }}</code></td>
                                <td><span class="admin-catalog-type {{ $currentTab['class'] }}">{{ $currentTab['type'] }}</span></td>
                                <td><strong>{{ $product->name }}</strong><small>{{ $product->brand }}</small></td>
                                <td>{{ $product->model ?: '—' }}</td>
                                <td>
                                    @if($tab === 'panels')
                                        {{ $product->tech_data['power_w'] ?? '—' }} W · randament {{ $product->tech_data['efficiency_percent'] ?? '—' }}%
                                    @elseif($tab === 'inverters')
                                        {{ $product->tech_data['nominal_power_w'] ?? '—' }} W · PV max. {{ $product->tech_data['max_pv_power_w'] ?? '—' }} W
                                    @else
                                        {{ $product->tech_data['capacity_kwh'] ?? '—' }} kWh · max. {{ $product->tech_data['max_power_w'] ?? '—' }} W
                                    @endif
                                </td>
                                <td class="admin-catalog-price">{{ $product->price_lei ? number_format((float) $product->price_lei, 0, ',', '.') . ' lei' : 'La cerere' }}</td>
                                <td><span class="admin-catalog-status {{ $product->active ? 'is-active' : '' }}">{{ $product->active ? 'Activ' : 'Inactiv' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="admin-catalog-table-empty">Nu există componente importate.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <footer class="admin-catalog-table-footer">
                <span>
                    @if($catalogProducts->total())
                        Afișare <b>{{ $catalogProducts->firstItem() }}–{{ $catalogProducts->lastItem() }}</b> din <b>{{ $catalogProducts->total() }}</b> componente
                    @else
                        Nu există componente
                    @endif
                </span>
                <div>
                    <form method="GET" action="{{ route('admin.catalog') }}" class="admin-catalog-per-page">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <label for="catalog-per-page">Pe pagină</label>
                        <select id="catalog-per-page" name="per_page" onchange="this.form.submit()">
                            @foreach($allowedPerPage as $option)
                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if($catalogProducts->hasPages())
                        <nav class="admin-catalog-pagination" aria-label="Paginare catalog">
                            @if($catalogProducts->onFirstPage())
                                <span class="is-disabled">← Anterior</span>
                            @else
                                <a href="{{ $catalogProducts->previousPageUrl() }}">← Anterior</a>
                            @endif
                            <span class="admin-catalog-page-current">Pagina {{ $catalogProducts->currentPage() }} din {{ $catalogProducts->lastPage() }}</span>
                            @if($catalogProducts->hasMorePages())
                                <a href="{{ $catalogProducts->nextPageUrl() }}">Următor →</a>
                            @else
                                <span class="is-disabled">Următor →</span>
                            @endif
                        </nav>
                    @endif
                </div>
            </footer>
        </section>
    </main>

    <x-app-footer />
</div>
@endsection
