@props(['system'])
<section id="dashboard" class="solar-card energy-stage lg:col-span-2">
    <div class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 sm:px-6">
        <div><div class="flex items-center gap-2"><i class="live-dot"></i><span class="text-xs font-semibold text-emerald-600">Simulare în desfășurare</span></div><div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400"><span x-text="formattedDateTime"></span><span class="rounded-full bg-violet-50 px-2 py-0.5 font-semibold text-violet-600">Acum</span></div></div>
        <div class="weather-chip"><span class="text-2xl">☀️</span><div><b class="block text-sm text-slate-800">28°C</b><span class="block text-[10px] text-slate-400" x-text="weatherLabel"></span></div><div class="ml-2 border-l border-slate-100 pl-3"><span class="block text-[9px] uppercase tracking-wide text-slate-400">Iradiație</span><b class="text-[11px] text-slate-600" x-text="irradiance+' W/m²'"></b></div></div>
    </div>
    <div class="relative min-h-[430px] overflow-hidden px-4 pb-5 pt-7 sm:px-6">
        <div class="absolute left-6 top-7 z-20 rounded-xl border border-amber-100 bg-white/90 px-3 py-2 shadow-sm backdrop-blur"><span class="block text-[10px] text-slate-400">Panouri active</span><b class="text-sm text-slate-700"><span x-text="enabledPanelCount"></span> panouri</b><span class="ml-2 text-[10px] font-semibold text-amber-500" x-text="(installedW/1000).toFixed(2)+' kWp'"></span></div>
        <div class="stat-float stat-production"><span class="stat-kicker">Producție</span><strong class="text-amber-500" x-text="formatW(result.solar_w)"></strong><div class="sparkline solar-spark"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div><small x-text="solarCapacityPercent+'% din capacitate'"></small></div>
        <div class="stat-float stat-consumption"><span class="stat-kicker">Consum</span><strong class="text-blue-500" x-text="formatW(result.served_load_w)"></strong><div class="sparkline load-spark"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div><small x-text="enabledConsumerUnitCount+' consumatori activi'"></small></div>
        <div class="solar-scene" aria-label="Flux energetic între panouri, invertor, casă și rețea">
            <div class="sun-orb">☀</div>
            <div class="scene-house"><div class="roof"><div class="roof-panels"><template x-for="panel in panels.slice(0,6)" :key="panel.id"><i :class="panel.enabled ? '' : 'opacity-20'"></i></template></div></div><div class="house-body"><span class="window"></span><span class="door"></span></div></div>
            <div class="scene-inverter"><span>◈</span><b>Invertor</b><small>{{ $system->inverter->name }}</small></div>
            <div class="scene-load"><span>⌂</span><b>Consumatori</b><small x-text="formatW(result.served_load_w)"></small></div>
            <div class="scene-grid"><span>⌁</span><b>Rețea</b><small x-text="gridAvailable ? gridLabel : 'Offline'"></small></div>
            <div class="energy-path path-solar" :class="result.solar_w > 0 && 'is-flowing'"></div>
            <div class="energy-path path-load" :class="result.served_load_w > 0 && 'is-flowing'"></div>
            <div class="energy-path path-grid" :class="{'is-flowing': Math.abs(result.grid_w) > 0, 'is-reversed': result.grid_w > 0}"></div>
        </div>
        <div class="bottom-flow-cards">
            <div class="mini-energy-card"><span class="mini-icon bg-emerald-50 text-emerald-600">↗</span><div><small>Excedent</small><b class="text-emerald-600" x-text="formatW(gridExport)"></b><span>Trimis în rețea</span></div></div>
            <div class="mini-energy-card"><span class="mini-icon bg-violet-50 text-violet-600">ϟ</span><div><small>Rețea</small><b class="text-slate-700"><span class="text-blue-500" x-text="'Import '+formatW(gridImport)"></span></b><span class="text-emerald-600" x-text="'Export '+formatW(gridExport)"></span></div></div>
            <div class="mini-energy-card" x-show="initial.battery.enabled"><span class="mini-icon bg-violet-50 text-violet-600">▣</span><div><small>Baterie</small><b class="text-violet-600" x-text="Number(result.soc).toFixed(0)+'%'"></b><span x-text="batteryDirection"></span></div></div>
        </div>
    </div>
</section>
