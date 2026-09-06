@props(['project','system'])
<section id="weather" class="solar-card p-5">
    <div class="card-heading"><h2>Simulare Curentă</h2><span class="rounded-full bg-violet-50 px-2 py-1 text-[9px] font-bold text-violet-600">LIVE</span></div>
    <dl class="simulation-details">
        <div><dt>Perioadă</dt><dd x-text="shortDate"></dd></div>
        <div><dt>Interval</dt><dd>00:00 - 24:00</dd></div>
        <div><dt>Pas simulare</dt><dd x-text="tickLabel"></dd></div>
        <div><dt>Locație</dt><dd>{{ $project->city }}, RO</dd></div>
        <div><dt>Panouri</dt><dd>{{ $system->panels->count() }} × {{ $system->panels->first()?->power_w ?? 0 }}W ({{ number_format($system->panels->sum('power_w')/1000,2) }} kWp)</dd></div>
        <div><dt>Baterie</dt><dd x-text="initial.battery.enabled ? Number(initial.battery.capacity_kwh).toFixed(2)+' kWh' : 'Fără baterie'"></dd></div>
    </dl>
    <a href="{{ route('projects.create') }}" class="edit-simulation">✎ Editează simularea</a>
</section>
