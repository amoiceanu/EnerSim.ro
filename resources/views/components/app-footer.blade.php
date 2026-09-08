@props(['project' => null])

<footer class="app-footer">
    <div class="app-footer-inner">
        <div class="app-footer-brand">
            <a href="{{ route('projects.index') }}" class="app-footer-logo" aria-label="EnerSim"><span aria-hidden="true">☀</span><strong>Ener<span>Sim</span></strong></a>
            <p>Simulează, compară și dimensionează sisteme fotovoltaice pentru fiecare proiect.</p>
        </div>
        <nav aria-label="Platformă"><h2>Platformă</h2><a href="{{ route('projects.index') }}">Proiectele mele</a><a href="{{ route('projects.create') }}">Proiect nou</a>@if ($project)<a href="{{ route('projects.show', $project) }}#simulation">Simulări</a>@endif</nav>
        <nav aria-label="Resurse"><h2>Resurse</h2>@if ($project)<a href="{{ route('projects.show', $project) }}#reports">Rapoarte</a><a href="{{ route('projects.show', $project) }}#guide">Ghid de utilizare</a><a href="{{ route('projects.show', $project) }}#about">Despre EnerSim</a>@else<a href="{{ route('projects.index') }}">Simulator fotovoltaic</a><a href="{{ route('projects.create') }}">Cum creezi un proiect</a>@endif</nav>
        <section class="app-footer-benefits" aria-labelledby="footer-benefits-title"><h2 id="footer-benefits-title">Ce poți face</h2><ul><li>Compari producția și consumul</li><li>Configurezi panouri, invertor și baterie</li><li>Generezi rapoarte pentru proiect</li></ul></section>
        <address class="app-footer-contact"><h2>Contact</h2><span>România</span><a href="mailto:contact@enersim.ro">contact@enersim.ro</a></address>
    </div>
    <div class="app-footer-bottom"><span>© {{ now()->year }} EnerSim. Toate drepturile rezervate.</span><span>Solar planning, simplificat.</span></div>
</footer>
