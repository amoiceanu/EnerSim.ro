@props(['system'])
<section id="system" class="solar-card p-5">
    <div class="card-heading"><h2>Stare Sistem</h2><span class="status-online"><i></i> Online</span></div>
    <div class="status-list">
        <div><span class="status-icon bg-amber-50 text-amber-500">☀</span><p><b>Panouri Solare</b><small><span x-text="enabledPanelCount"></span> / <span x-text="panels.length"></span> active</small></p><em>✓</em></div>
        <div><span class="status-icon bg-violet-50 text-violet-500">◈</span><p><b>Invertor</b><small>Funcționează normal</small></p><em>✓</em></div>
        <div><span class="status-icon bg-emerald-50 text-emerald-500">⌁</span><p><b>Conexiune Rețea</b><small x-text="gridAvailable ? 'Conectat' : 'Deconectat'"></small></p><em :class="!gridAvailable && 'warning'" x-text="gridAvailable ? '✓' : '!'">✓</em></div>
        <div><span class="status-icon bg-slate-100 text-slate-400">▣</span><p><b>Baterie</b><small x-text="initial.battery.enabled ? Number(result.soc).toFixed(0)+'% disponibil' : 'Nu este instalată'"></small></p><em :class="!initial.battery.enabled && 'neutral'" x-text="initial.battery.enabled ? '✓' : '—'"></em></div>
    </div>
</section>
