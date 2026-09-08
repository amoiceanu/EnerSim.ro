<section x-show="activeNav==='inverters'" x-cloak class="dedicated-page inverters-page">
    <div class="dedicated-page-head">
        <div><span>Conversie și control</span><h2>Invertoare SolarTech</h2><p>Compară specificațiile și schimbă invertorul folosit în simularea proiectului.</p></div>
        <div class="page-summary-pill"><b x-text="formatW(initial.inverter.nominal_power_w)"></b><small>selectat</small></div>
    </div>

    <section class="current-inverter-card">
        <span class="current-inverter-icon">◈</span>
        <div class="min-w-0"><small>Invertor curent</small><h3 x-text="initial.inverter.name"></h3><p>Configurația activă este folosită imediat în calculele de producție, limitare și backup.</p></div>
        <div class="current-inverter-stats"><div><small>Putere AC</small><b x-text="formatW(initial.inverter.nominal_power_w)"></b></div><div><small>Intrare PV</small><b x-text="formatW(initial.inverter.max_pv_power_w)"></b></div><div><small>Randament</small><b x-text="Number(initial.inverter.efficiency).toFixed(1)+'%'"></b></div></div>
    </section>
    <section class="inverter-power-chart" aria-label="Comparație putere sistem și invertor">
        <div class="inverter-power-chart-head"><div><span>Compatibilitate putere</span><h3>Sistem FV vs. limită invertor</h3></div><b :class="installedW<=Number(initial.inverter.max_pv_power_w) ? 'is-compatible' : 'is-warning'" x-text="installedW<=Number(initial.inverter.max_pv_power_w) ? 'În limită' : 'Limită depășită'"></b></div>
        <div class="inverter-power-chart-row is-system"><div><span aria-hidden="true">☀</span><b>Sistem FV instalat</b><strong x-text="formatW(installedW)"></strong></div><i><em :style="`width:${Math.min(100, installedW / Math.max(Number(initial.inverter.max_pv_power_w), installedW, 1) * 100)}%`"></em></i></div>
        <div class="inverter-power-chart-row is-inverter"><div><span aria-hidden="true">◈</span><b>Limită PV invertor</b><strong x-text="formatW(initial.inverter.max_pv_power_w)"></strong></div><i><em :style="`width:${Math.min(100, Number(initial.inverter.max_pv_power_w) / Math.max(Number(initial.inverter.max_pv_power_w), installedW, 1) * 100)}%`"></em></i></div>
    </section>

    <div class="inverter-selection-head"><div><h3>Alege invertorul</h3><p>Prețuri și specificații preluate din catalogul SolarTech.</p></div><span x-text="filteredInverterPresets.length+' din '+inverterPresets.length+' modele' "></span></div>
    <section class="inverter-filter-panel" aria-label="Filtre invertoare">
        <div class="inverter-filter-search"><label for="inverter-search">Caută invertor</label><input id="inverter-search" type="search" x-model="inverterSearch" placeholder="Nume, model sau brand"></div>
        <label>Putere min. (W)<input type="number" min="0" x-model.number="inverterPowerMin" placeholder="Oricare"></label>
        <label>Putere max. (W)<input type="number" min="0" x-model.number="inverterPowerMax" placeholder="Oricare"></label>
        <label>Faze<select x-model="inverterPhase"><option value="all">Toate</option><option value="1">Monofazat</option><option value="3">Trifazat</option></select></label>
        <label>Disponibilitate<select x-model="inverterStock"><option value="all">Oricare</option><option value="in_stock">În stoc</option><option value="on_order">La comandă</option></select></label>
        <label>Panouri instalate<select x-model="inverterCompatibility"><option value="all">Toate</option><option value="compatible">Compatibile</option><option value="overload">Putere depășită</option></select></label>
        <button type="button" @click="clearInverterFilters()">Resetează filtrele</button>
    </section>
    <div class="inverter-product-grid">
        <template x-for="preset in filteredInverterPresets" :key="preset.key">
            <article class="inverter-product" :class="isCurrentInverter(preset) && 'is-selected'">
                <header><span>◈</span><div><small x-text="preset.brand"></small><h3 x-text="preset.name"></h3><p x-text="preset.model"></p></div><i x-show="isCurrentInverter(preset)">Selectat</i></header>
                <div class="inverter-power"><b x-text="formatW(preset.nominal_power_w)"></b><span x-text="preset.phases===3?'Trifazat':'Monofazat'"></span></div>
                <dl><div><dt>Putere PV maximă</dt><dd x-text="formatW(preset.max_pv_power_w)"></dd></div><div><dt>Trackere MPPT</dt><dd x-text="preset.mppt_count"></dd></div><div><dt>Randament maxim</dt><dd x-text="Number(preset.efficiency).toFixed(1)+'%'"></dd></div><div><dt>Compatibilitate panouri</dt><dd :class="installedW<=Number(preset.max_pv_power_w)?'is-compatible':'is-warning'" x-text="installedW<=Number(preset.max_pv_power_w)?'Compatibil':'Putere depășită'"></dd></div></dl>
                <div class="inverter-price"><div><small>Preț echipament</small><b x-text="formatMoney(preset.price_lei)"></b><span :class="preset.stock_status==='in_stock'?'in-stock':'on-order'" x-text="preset.stock_status==='in_stock'?'În stoc':'În curs de restocare'"></span></div><a :href="preset.source_url" target="_blank" rel="noreferrer">Fișă produs ↗</a></div>
                <button type="button" @click="selectInverter(preset)" :disabled="inverterBusy || isCurrentInverter(preset)" x-text="isCurrentInverter(preset)?'Invertor selectat':'Selectează acest invertor'"></button>
            </article>
        </template>
    </div>
    <p class="inverter-filter-empty" x-show="!filteredInverterPresets.length">Nu am găsit invertoare care să corespundă filtrelor selectate.</p>
    <div class="inverter-selection-note"><span>!</span><p><b>Verifică înainte de instalare</b>Compatibilitatea electrică finală depinde și de tensiunea șirurilor, curentul panourilor, bateria aleasă și configurația rețelei.</p></div>
</section>
