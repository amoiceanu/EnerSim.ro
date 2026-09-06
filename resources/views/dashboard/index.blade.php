@extends('layouts.app', ['title' => 'EnerSim · Simulator Panouri Solare'])
@section('content')
<div x-data="energySimulator({{ Illuminate\Support\Js::from($simulatorData) }})" :class="nightMode && 'night-mode'" class="solar-app min-h-screen">
    <x-sidebar :project="$project" :system="$system" :assessment="$assessment" />
    <x-floating-pro-tip />
    <div class="min-w-0 lg:ml-[270px]">
        <x-topbar :project="$project" />
        <main class="solar-main">
            @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">{{ session('success') }}</div>@endif
            <x-simulation-options />
            <x-report-options :project="$project" :reports="$reportDefinitions" />
            <x-panels-page />
            <x-inverters-page />
            <x-consumers-page />
            <x-battery-page />
            <x-weather-page />
            <x-system-page />
            <x-feed-import-page :project="$project" :summary="$catalogSummary" />
            <x-guide-page />
            <x-about-page />
            <x-dashboard-home />
        </main>
    </div>
</div>
@endsection
