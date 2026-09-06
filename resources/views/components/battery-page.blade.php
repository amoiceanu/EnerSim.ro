<section x-show="activeNav==='battery'" x-cloak class="dedicated-page">
    <div class="dedicated-page-head"><div><span>Stocare energie</span><h2>Bateriile proiectului</h2><p>Vezi bateria instalată sau alege o configurație potrivită pentru autonomie.</p></div><div class="page-summary-pill"><b x-text="initial.battery.enabled ? Number(initial.battery.capacity_kwh).toFixed(1) : '0'"></b><small>kWh</small></div></div>
    <div class="dedicated-content-narrow"><x-battery-manager /></div>
</section>
