@props(['project', 'document'])
<header class="report-letterhead">
    <a href="{{ route('projects.show', $project) }}" class="report-brand" aria-label="EnerSim">
        <span><x-report.icon name="sun" /></span><strong>Ener<span>Sim</span></strong>
    </a>
    <div><small>Raport tehnic</small><b>{{ $document['badge'] }}</b></div>
</header>
