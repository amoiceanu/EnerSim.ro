@extends('layouts.app', ['title' => 'EnerSim · Planifică energia solară'])

@section('content')
<div class="public-page">
    <x-public-nav />
    <main>
        <section class="home-hero">
            <div><p>SIMULATOR FOTOVOLTAIC</p><h1>Planifică energia solară pentru fiecare proiect.</h1><span>EnerSim te ajută să compari producția, consumul, stocarea și costurile înainte de instalare.</span><div class="home-hero-actions"><a href="{{ route('projects.create') }}" class="public-nav-primary">Intră în simulator <b>→</b></a><a href="#cum-functioneaza" class="home-text-link">Vezi cum funcționează</a></div></div>
            <aside class="home-preview" aria-label="Previzualizare simulator"><div><span>☀</span><small>PRODUCȚIE ESTIMATĂ</small><strong>14,8 kWh</strong><em>Astăzi · condiții însorite</em></div><div class="home-preview-flow"><b>Panouri</b><i>→</i><b>Invertor</b><i>→</i><b>Casă</b></div><dl><div><dt>Autoconsum</dt><dd>78%</dd></div><div><dt>Economie</dt><dd>186 lei</dd></div></dl></aside>
        </section>
        <section id="cum-functioneaza" class="home-section"><p>PAȘI SIMPLI</p><h2>De la consumatori la sistemul potrivit.</h2><div class="home-step-grid"><article><span>01</span><h3>Creezi proiectul</h3><p>Adaugi locația și aparatele pe care vrei să le alimentezi.</p></article><article><span>02</span><h3>Configurezi sistemul</h3><p>Alegi panouri, invertor, baterie și parametrii instalației.</p></article><article><span>03</span><h3>Simulezi și compari</h3><p>Verifici producția, acoperirea consumului și costurile estimate.</p></article></div></section>
        <section id="beneficii" class="home-section home-benefits"><div><p>PENTRU PROIECTE REALE</p><h2>Decizii clare, într-un singur loc.</h2></div><ul><li>Simulări la dată, oră și vreme selectată</li><li>Catalog de componente și devize orientative</li><li>Rapoarte pregătite pentru PDF</li><li>Proiecte fără limită</li></ul></section>
    </main>
    <x-app-footer />
</div>
@endsection
