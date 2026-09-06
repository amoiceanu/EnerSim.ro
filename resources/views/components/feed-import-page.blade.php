@props(['project', 'summary'])
<section x-show="activeNav==='feed'" x-cloak class="dedicated-page feed-import-page">
    <div class="dedicated-page-head">
        <div><span>Administrare catalog</span><h2>Import feed</h2><p>Actualizează panourile, invertoarele și bateriile dintr-un catalog Excel.</p></div>
        <div class="page-summary-pill"><b>{{ $summary['total'] }}</b><small>produse</small></div>
    </div>

    @if(session('feed_import_result'))
        @php($importResult = session('feed_import_result'))
        <div class="feed-import-success" role="status">
            <span aria-hidden="true">✓</span>
            <div><b>Import finalizat</b><p>{{ $importResult['created'] }} produse adăugate, {{ $importResult['updated'] }} actualizate și {{ $importResult['skipped'] }} omise.</p></div>
        </div>
    @endif

    <div class="feed-import-layout">
        <form method="POST" action="{{ route('feed.import', $project) }}" enctype="multipart/form-data" class="feed-import-card" x-data="{ fileName: '' }">
            @csrf
            <div class="feed-import-card-head"><span aria-hidden="true">⇧</span><div><small>Fișier sursă</small><h3>Încarcă feedul SolarTech</h3></div></div>
            <label for="equipment-feed" class="feed-dropzone" :class="fileName && 'has-file'">
                <span class="feed-dropzone-icon" aria-hidden="true">▤</span>
                <b x-text="fileName || 'Selectează un fișier Excel'"></b>
                <small>.xlsx · maximum 10 MB</small>
                <input id="equipment-feed" name="feed" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required @change="fileName = $event.target.files[0]?.name || ''">
            </label>
            @error('feed')<p class="feed-import-error" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="feed-import-submit"><span aria-hidden="true">⇧</span> Importă și actualizează catalogul</button>
            <p class="feed-import-footnote">Produsele sunt identificate după SKU sau URL. Reimportarea actualizează înregistrările existente și nu creează duplicate.</p>
        </form>

        <aside class="feed-catalog-card" aria-label="Starea catalogului">
            <small>Catalog curent</small>
            <h3>Componente disponibile</h3>
            <dl>
                <div><dt><span class="tone-solar" aria-hidden="true">☀</span> Panouri</dt><dd>{{ $summary['panels'] }}</dd></div>
                <div><dt><span class="tone-violet" aria-hidden="true">◈</span> Invertoare</dt><dd>{{ $summary['inverters'] }}</dd></div>
                <div><dt><span class="tone-green" aria-hidden="true">▣</span> Baterii</dt><dd>{{ $summary['batteries'] }}</dd></div>
            </dl>
            <div class="feed-last-update"><span aria-hidden="true">◷</span><p><small>Ultima actualizare a sursei</small><b>{{ $summary['updated_at'] ?? 'Nedisponibilă' }}</b></p></div>
        </aside>
    </div>

    <section class="feed-format-card">
        <div><span aria-hidden="true">i</span><p><b>Format acceptat</b>Workbook-ul poate conține foile „Panouri”, „Invertoare” și „Baterii”. Anteturile sunt citite de pe rândul 4.</p></div>
        <ul><li>SKU, produs și URL sursă sunt obligatorii</li><li>Prețurile și stocul sunt actualizate automat</li><li>Datele tehnice sunt păstrate în catalog</li></ul>
    </section>
</section>
