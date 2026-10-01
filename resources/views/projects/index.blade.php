<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Proiectele mele · EnerSim</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="projects-page min-h-screen font-sans antialiased">
    <x-ad-placeholders />
    <header class="projects-header">
        <div class="projects-shell projects-header-inner">
            <div class="projects-brand">
                <div class="projects-brand-mark" aria-hidden="true">☀</div>
                <div>
                    <div class="projects-brand-name">Ener<span>Sim</span></div>
                    <p>Portofoliul tău energetic</p>
                </div>
            </div>
            <div class="projects-header-actions">@if(auth()->user()?->is_admin)<a href="{{ route('admin.projects.index') }}" class="projects-admin-link">Administrare</a>@endif<a href="{{ route('projects.create') }}" class="projects-new-button"><span aria-hidden="true">＋</span> Proiect nou</a></div>
        </div>
    </header>

    <main class="projects-shell projects-main">
        @if(session('success'))
            <div class="projects-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="projects-intro" aria-labelledby="projects-title">
            <p>Workspace</p>
            <h1 id="projects-title">Proiectele mele</h1>
            <div>Fiecare adresă are consumatorii, instalația și simulările sale. Poți crea oricâte proiecte dorești. <span class="projects-count-badge">{{ $projects->count() }} {{ $projects->count() === 1 ? 'proiect' : 'proiecte' }}</span></div>
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
                    @php($solarPower = (float) ($a['pv_w'] ?? 0))
                    @php($consumptionPower = (float) ($a['continuous_w'] ?? 0))
                    @php($chartMaximum = max($solarPower, $consumptionPower, 1))
                    <article class="project-card">
                        <div class="project-card-top">
                            <div class="project-identity">
                                <div class="project-home-icon" aria-hidden="true">⌂</div>
                                <div>
                                    <h2>{{ $project->name }}</h2>
                                    <p><span aria-hidden="true">⌖</span> {{ $project->city }}, {{ $project->county }}</p>
                                </div>
                            </div>
                            <span class="project-status project-status-{{ $a['status'] }}">
                                @if($a['status'] === 'partial')
                                    <button type="button" class="project-status-warning" aria-label="De ce este sistemul la limită?" aria-describedby="status-info-{{ $project->id }}">⚠</button>
                                    <span id="status-info-{{ $project->id }}" class="project-status-tooltip" role="tooltip">Sistemul acoperă doar parțial necesarul. Verifică puterea instalată, consumul simultan și energia zilnică înainte de configurarea finală.</span>
                                @endif
                                {{ mb_strtoupper($a['label']) }}
                            </span>
                        </div>

                        <section class="project-energy-chart" aria-label="Comparație producție solară și consum">
                            <div class="project-chart-legend"><span class="is-solar"><i></i> Producție solară</span><span class="is-consumption"><i></i> Consum</span></div>
                            <div class="project-chart-row is-solar"><span aria-hidden="true">☀</span><div><b>SOLAR</b><i><em style="width: {{ ($solarPower / $chartMaximum) * 100 }}%"></em></i></div><strong>{{ number_format($solarPower / 1000, 2) }} kWp</strong></div>
                            <div class="project-chart-row is-consumption"><span aria-hidden="true">ϟ</span><div><b>CONSUM</b><i><em style="width: {{ ($consumptionPower / $chartMaximum) * 100 }}%"></em></i></div><strong>{{ number_format($consumptionPower / 1000, 2) }} kW</strong></div>
                        </section>

                        <div class="project-card-footer">
                        <dl class="project-score-row">
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
                            <div class="project-cost-chip">
                                <span class="project-cost-icon" aria-hidden="true">₿</span>
                                <div>
                                    <dt>Cost sistem</dt>
                                    <dd>{{ $project->system_cost }}</dd>
                                </div>
                            </div>
                        </dl>

                        <div class="project-actions">
                            <a href="{{ route('projects.show', $project) }}" class="projects-open-button">Deschide simulatorul <span aria-hidden="true">→</span></a>
                            @auth<form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Ștergi proiectul și toate datele sale?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="project-delete-button"><span aria-hidden="true">×</span> Șterge</button>
                            </form>@endauth
                        </div>
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
    <x-app-footer />
</body>
</html>
