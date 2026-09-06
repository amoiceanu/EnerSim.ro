<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Proiectele mele · EnerSim</title>
    @vite(['resources/css/app.css', 'resources/css/app.less', 'resources/js/app.js'])
</head>
<body class="projects-page min-h-screen font-sans antialiased">
    <header class="projects-header">
        <div class="projects-shell projects-header-inner">
            <div class="projects-brand">
                <div class="projects-brand-mark" aria-hidden="true">☀</div>
                <div>
                    <div class="projects-brand-name">Ener<span>Sim</span></div>
                    <p>Portofoliul tău energetic</p>
                </div>
            </div>
            <a href="{{ route('projects.create') }}" class="projects-new-button"><span aria-hidden="true">＋</span> Proiect nou</a>
        </div>
    </header>

    <main class="projects-shell projects-main">
        @if(session('success'))
            <div class="projects-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="projects-intro" aria-labelledby="projects-title">
            <p>Workspace</p>
            <h1 id="projects-title">Proiectele mele</h1>
            <div>Fiecare adresă are consumatorii, instalația și simulările sale. Poți crea oricâte proiecte dorești.</div>
        </section>

        @if($projects->isEmpty())
            <section class="projects-empty" aria-labelledby="empty-projects-title">
                <div class="projects-empty-icon" aria-hidden="true">⌂</div>
                <h2 id="empty-projects-title">Creează primul proiect</h2>
                <p>Wizard-ul te conduce de la locație și consumatori până la panouri, invertor, baterie și verdictul de dimensionare.</p>
                <a href="{{ route('projects.create') }}" class="projects-open-button">Începe configurarea <span aria-hidden="true">→</span></a>
            </section>
        @else
            <section class="projects-grid" aria-label="Lista proiectelor">
                @foreach($projects as $project)
                    @php($system = $project->systems->first())
                    @php($a = $project->assessment)
                    <article class="project-card">
                        <div class="project-card-top">
                            <div class="project-identity">
                                <div class="project-home-icon" aria-hidden="true">⌂</div>
                                <div>
                                    <h2>{{ $project->name }}</h2>
                                    <p><span aria-hidden="true">⌖</span> {{ $project->city }}, {{ $project->county }}</p>
                                </div>
                            </div>
                            <span class="project-status project-status-{{ $a['status'] }}">{{ mb_strtoupper($a['label']) }}</span>
                        </div>

                        <dl class="project-metrics">
                            <div class="project-metric project-metric-solar"><span class="project-metric-icon" aria-hidden="true">☀</span><div><dt>Solar</dt><dd>{{ number_format(($a['pv_w'] ?? 0) / 1000, 2) }} kWp</dd></div></div>
                            <div class="project-metric project-metric-consumption"><span class="project-metric-icon" aria-hidden="true">ϟ</span><div><dt>Consum</dt><dd>{{ number_format(($a['continuous_w'] ?? 0) / 1000, 2) }} kW</dd></div></div>
                            <div class="project-metric project-metric-score">
                                <span class="project-metric-icon" aria-hidden="true">✓</span>
                                <div class="project-metric-copy">
                                    <div class="project-metric-label">
                                        <dt>Scor</dt>
                                        <button type="button" class="score-info-button" aria-label="Ce înseamnă scorul?" aria-describedby="score-info-{{ $project->id }}">i</button>
                                        <span id="score-info-{{ $project->id }}" class="score-info-tooltip" role="tooltip">
                                            Scorul arată câte dintre cele 4 verificări sunt îndeplinite: putere continuă, vârf de pornire, energie zilnică estimată și ieșire backup EPS. Fiecare verificare valorează 25%.
                                            <small>100% = sistem adecvat · 50–75% = la limită · 0–25% = insuficient</small>
                                        </span>
                                    </div>
                                    <dd>{{ $a['score'] }}%</dd>
                                </div>
                            </div>
                        </dl>

                        <div class="project-actions">
                            <a href="{{ route('projects.show', $project) }}" class="projects-open-button">Deschide simulatorul <span aria-hidden="true">→</span></a>
                            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Ștergi proiectul și toate datele sale?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="project-delete-button"><span aria-hidden="true">×</span> Șterge</button>
                            </form>
                        </div>
                    </article>
                @endforeach

                <a href="{{ route('projects.create') }}" class="project-add-card">
                    <span class="project-add-icon" aria-hidden="true">＋</span>
                    <b>Adaugă alt proiect</b>
                    <span>Fără limită de proiecte</span>
                </a>
            </section>
        @endif
    </main>
</body>
</html>
