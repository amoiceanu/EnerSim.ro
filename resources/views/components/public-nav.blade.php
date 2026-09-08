<header class="public-nav">
    <a href="{{ route('home') }}" class="public-nav-logo" aria-label="EnerSim"><span aria-hidden="true">☀</span><strong>Ener<span>Sim</span></strong></a>
    <nav aria-label="Navigație principală"><a href="{{ route('home') }}#cum-functioneaza">Cum funcționează</a><a href="{{ route('home') }}#beneficii">Beneficii</a></nav>
    <div class="public-nav-actions">
        @auth
            <a href="{{ route('projects.index') }}" class="public-nav-secondary">Proiectele mele</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="public-nav-secondary">Ieșire</button></form>
        @else
            <a href="{{ route('login') }}" class="public-nav-secondary">Autentificare</a>
            <a href="{{ route('projects.create') }}" class="public-nav-primary">Deschide simulatorul</a>
        @endauth
    </div>
</header>
