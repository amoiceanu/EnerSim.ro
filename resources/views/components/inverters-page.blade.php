<section x-show="activeNav==='inverters'" x-cloak class="dedicated-page">
    <div class="dedicated-page-head">
        <div><span>Conversie și control</span><h2>Invertoare SolarTech</h2><p>Compară specificațiile și schimbă invertorul folosit în simularea proiectului.</p></div>
        <div class="page-summary-pill"><b x-text="formatW(initial.inverter.nominal_power_w)"></b><small>selectat</small></div>
    </div>

    <section class="current-inverter-card">
        <span class="current-inverter-icon">◈</span>
        <div class="min-w-0"><small>Invertor curent</small><h3 x-text="initial.inverter.name"></h3><p>Configurația activă este folosită imediat în calculele de producție, limitare și backup.</p></div>
        <div class="current-inverter-stats"><div><small>Putere AC</small><b x-text="formatW(initial.inverter.nominal_power_w)"></b></div><div><small>Intrare PV</small><b x-text="formatW(initial.inverter.max_pv_power_w)"></b></div><div><small>Randament</small><b x-text="Number(initial.inverter.efficiency).toFixed(1)+'%'"></b></div></div>
    </section>

    <div class="inverter-selection-head"><div><h3>Alege invertorul</h3><p>Prețuri și specificații preluate din catalogul SolarTech.</p></div><span x-text="inverterPresets.length+' modele disponibile'"></span></div>
    <div class="inverter-product-grid">
        <template x-for="preset in inverterPresets" :key="preset.key">
            <article class="inverter-product" :class="isCurrentInverter(preset) && 'is-selected'">
                <header><span>◈</span><div><small x-text="preset.brand"></small><h3 x-text="preset.name"></h3><p x-text="preset.model"></p></div><i x-show="isCurrentInverter(preset)">Selectat</i></header>
                <div class="inverter-power"><b x-text="formatW(preset.nominal_power_w)"></b><span x-text="preset.phases===3?'Trifazat':'Monofazat'"></span></div>
                <dl><div><dt>Putere PV maximă</dt><dd x-text="formatW(preset.max_pv_power_w)"></dd></div><div><dt>Trackere MPPT</dt><dd x-text="preset.mppt_count"></dd></div><div><dt>Randament maxim</dt><dd x-text="Number(preset.efficiency).toFixed(1)+'%'"></dd></div><div><dt>Compatibilitate panouri</dt><dd :class="installedW<=Number(preset.max_pv_power_w)?'is-compatible':'is-warning'" x-text="installedW<=Number(preset.max_pv_power_w)?'Compatibil':'Putere depășită'"></dd></div></dl>
                <div class="inverter-price"><div><small>Preț echipament</small><b x-text="formatMoney(preset.price_lei)"></b><span :class="preset.stock_status==='in_stock'?'in-stock':'on-order'" x-text="preset.stock_status==='in_stock'?'În stoc':'În curs de restocare'"></span></div><a :href="preset.source_url" target="_blank" rel="noreferrer">Fișă produs ↗</a></div>
                <button type="button" @click="selectInverter(preset)" :disabled="inverterBusy || isCurrentInverter(preset)" x-text="isCurrentInverter(preset)?'Invertor selectat':'Selectează acest invertor'"></button>
            </article>
        </template>
    </div>
    <div class="inverter-selection-note"><span>!</span><p><b>Verifică înainte de instalare</b>Compatibilitatea electrică finală depinde și de tensiunea șirurilor, curentul panourilor, bateria aleasă și configurația rețelei.</p></div>
</section>
