<section id="reports" class="solar-card p-5">
    <div class="card-heading"><h2>Sumar Zilnic</h2><span class="text-[10px] text-slate-400" x-text="shortDate"></span></div>
    <dl class="summary-list">
        <div><dt><i class="bg-amber-400"></i>Producție totală</dt><dd class="text-amber-500" x-text="energy.solar.toFixed(2)+' kWh'"></dd></div>
        <div><dt><i class="bg-blue-400"></i>Consum total</dt><dd class="text-blue-500" x-text="energy.load.toFixed(2)+' kWh'"></dd></div>
        <div><dt><i class="bg-emerald-400"></i>Excedent trimis rețea</dt><dd class="text-emerald-600" x-text="energy.export.toFixed(2)+' kWh'"></dd></div>
        <div><dt><i class="bg-violet-400"></i>Autonomie</dt><dd class="text-violet-600" x-text="initial.battery.enabled ? Number(result.soc).toFixed(0)+'%' : '—'"></dd></div>
    </dl>
    <button type="button" @click="navigate('reports')" class="report-link">Vezi raport complet →</button>
</section>
