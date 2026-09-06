@props(['project', 'reports'])
<section class="report-options-page" x-show="activeNav==='reports'" x-cloak>
    <div class="simulation-options-head">
        <div><span class="simulation-eyebrow">Centru de rapoarte</span><h2>Alege raportul dorit</h2><p>Analize relevante construite din configurația și rezultatele proiectului curent.</p></div>
        <button type="button" @click="navigate('dashboard')" class="secondary-button">← Dashboard</button>
    </div>

    <div class="report-option-grid">
        @foreach($reports as $id => $report)
            <a href="{{ route('reports.show', [$project, $id]) }}" target="_blank" rel="noopener" class="report-option-card" aria-label="Deschide raportul {{ $report['title'] }} într-un tab nou">
                <span class="report-option-icon" aria-hidden="true">{{ $report['icon'] }}</span>
                <span class="min-w-0 flex-1"><small>{{ $report['badge'] }}</small><b>{{ $report['title'] }}</b><p>{{ $report['description'] }}</p><em>Document A4 · pregătit pentru PDF</em></span>
                <i aria-hidden="true">↗</i>
            </a>
        @endforeach
    </div>

    <div class="report-a4-help"><span aria-hidden="true">A4</span><p><b>Rapoarte gata pentru export</b>Fiecare raport se deschide într-un tab nou și poate fi salvat ca PDF folosind butonul „Exportă PDF”.</p></div>
</section>
