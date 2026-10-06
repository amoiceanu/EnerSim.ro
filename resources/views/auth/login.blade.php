@extends('layouts.app', ['title' => 'Autentificare · EnerSim'])

@section('content')
<div class="public-page login-page">
    <x-public-nav />
    <main class="login-main"><section class="login-card"><a href="{{ route('home') }}" class="login-back">← Înapoi la pagina principală</a><p>ACCES SECURIZAT</p><h1>Autentificare</h1><span>Momentan, simulatorul este disponibil doar pentru administrator.</span>
        <form method="POST" action="{{ route('login.store') }}">@csrf
            <label>Utilizator sau email<input name="email" type="text" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
            <label>Parolă<input name="password" type="password" autocomplete="current-password" required></label>
            <label class="login-remember"><input name="remember" type="checkbox" value="1"> Păstrează-mă autentificat</label>
            @if ($errors->any())<p class="login-error" role="alert">{{ $errors->first() }}</p>@endif
            <button type="submit" class="public-nav-primary">Intră în EnerSim <b>→</b></button>
        </form>
    </section></main>
    <x-app-footer />
</div>
@endsection
