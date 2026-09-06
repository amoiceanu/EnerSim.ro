<section x-show="activeNav==='dashboard'" x-cloak class="dashboard-home" aria-label="Dashboard energetic">
    <div class="dashboard-home-head">
        <div>
            <span>Privire de ansamblu</span>
            <h2>Energia casei, pe înțelesul tău</h2>
            <p>Urmărește producția, consumul și starea sistemului într-un singur loc.</p>
        </div>
        <label class="dashboard-date-picker">
            <span>Data analizată</span>
            <input type="date" :value="time.toISOString().slice(0, 10)" @change="time = new Date($event.target.value+'T14:00:00'); tick()" aria-label="Data analizată">
        </label>
    </div>

    <section class="dashboard-metric-grid" aria-label="Indicatori energetici">
        <article class="dashboard-metric is-solar"><span class="dashboard-metric-icon">☀</span><div><small>Producție azi</small><strong>14.8 kWh</strong><em>↑ 12% față de ieri</em></div></article>
        <article class="dashboard-metric is-consumption"><span class="dashboard-metric-icon">ϟ</span><div><small>Consum azi</small><strong>11.2 kWh</strong><em>↓ 6% față de ieri</em></div></article>
        <article class="dashboard-metric is-autonomy"><span class="dashboard-metric-icon">⌁</span><div><small>Autoconsum</small><strong>78%</strong><em>↑ 4% față de ieri</em></div></article>
        <article class="dashboard-metric is-savings"><span class="dashboard-metric-icon">◈</span><div><small>Economii luna aceasta</small><strong>186 lei</strong><em>↑ 24 lei față de luna trecută</em></div></article>
    </section>

    <div class="dashboard-insight-grid">
        <section class="dashboard-card dashboard-chart-card">
            <div class="dashboard-card-head"><div><span>Analiză zilnică</span><h3>Flux energetic – Astăzi</h3></div><div class="dashboard-chart-legend"><i class="is-production"></i>Producție <i class="is-consumption"></i>Consum <i class="is-autonomy"></i>Autoconsum</div></div>
            <div class="dashboard-chart-wrap">
                <div class="dashboard-chart-y"><span>4 kW</span><span>3 kW</span><span>2 kW</span><span>1 kW</span><span>0</span></div>
                <svg class="dashboard-energy-chart" viewBox="0 0 680 270" role="img" aria-label="Grafic cu producție, consum și autoconsum pentru ziua curentă">
                    <defs><linearGradient id="dashboard-production-gradient" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#f5a623" stop-opacity=".26"/><stop offset="1" stop-color="#f5a623" stop-opacity="0"/></linearGradient></defs>
                    <g class="dashboard-chart-grid"><path d="M0 20H680M0 75H680M0 130H680M0 185H680M0 240H680"/></g>
                    <path class="dashboard-chart-area" d="M0 240 C55 240 72 234 108 215 S168 145 210 105 S274 43 340 30 S414 58 462 111 S531 195 590 223 S642 238 680 240 V240H0Z"/>
                    <path class="dashboard-chart-line is-production" d="M0 240 C55 240 72 234 108 215 S168 145 210 105 S274 43 340 30 S414 58 462 111 S531 195 590 223 S642 238 680 240"/>
                    <path class="dashboard-chart-line is-consumption" d="M0 196 C52 189 87 201 132 178 S208 160 254 169 S323 131 374 152 S456 173 508 151 S579 165 622 151 S661 156 680 147"/>
                    <path class="dashboard-chart-line is-autonomy" d="M0 236 C54 233 95 229 140 207 S205 176 255 170 S330 134 375 150 S439 173 482 158 S546 171 590 164 S640 162 680 157"/>
                </svg>
                <div class="dashboard-chart-x"><span>06:00</span><span>09:00</span><span>12:00</span><span>15:00</span><span>18:00</span><span>21:00</span></div>
            </div>
        </section>

        <section class="dashboard-card dashboard-weather-card">
            <div class="dashboard-card-head"><div><span>Locație proiect</span><h3>Condiții meteo</h3></div><button type="button" @click="navigate('weather')">Detalii →</button></div>
            <div class="dashboard-weather-main"><span>☀</span><div><strong>24°</strong><b>Însorit</b><small>Provița de Sus, Prahova</small></div></div>
            <dl class="dashboard-weather-details"><div><dt>Radiație</dt><dd>782 W/m²</dd></div><div><dt>Vânt</dt><dd>8 km/h</dd></div><div><dt>Umiditate</dt><dd>42%</dd></div></dl>
            <p class="dashboard-weather-note"><i></i> Condiții foarte bune pentru producția solară.</p>
        </section>

        <section class="dashboard-card dashboard-flow-card">
            <div class="dashboard-card-head"><div><span>Monitorizare live</span><h3>Flux sistem în timp real</h3></div><b class="dashboard-live-dot"><i></i> Live</b></div>
            <div class="dashboard-flow-diagram">
                <div class="dashboard-flow-node is-solar"><span>☀</span><b>Panouri solare</b><small x-text="formatW(result.solar_w || 3030)"></small></div>
                <i class="dashboard-flow-arrow">→</i>
                <div class="dashboard-flow-node is-inverter"><span>◈</span><b>Invertor</b><small>97.6% randament</small></div>
                <i class="dashboard-flow-arrow">→</i>
                <div class="dashboard-flow-targets"><div class="dashboard-flow-node is-house"><span>⌂</span><b>Casă</b><small x-text="formatW(result.load_w || 1120)"></small></div><div class="dashboard-flow-node is-grid"><span>⌁</span><b>Rețea</b><small>Export 1.9 kW</small></div></div>
            </div>
        </section>
    </div>

    <div class="dashboard-bottom-grid">
        <section class="dashboard-card dashboard-recommendations-card">
            <div class="dashboard-card-head"><div><span>Optimizare</span><h3>Recomandări inteligente</h3></div><button type="button" @click="navigate('simulation')">Vezi simulări →</button></div>
            <article><span class="is-violet">◈</span><div><b>Folosește consumatorii mari la prânz</b><p>Ai surplus solar estimat între 11:00 și 15:00. Programează boilerul sau mașina de spălat în acest interval.</p><button type="button" @click="navigate('consumers')">Gestionează consumatorii</button></div></article>
            <article><span class="is-solar">☀</span><div><b>Producție excelentă astăzi</b><p>Vremea însorită favorizează autoconsumul. Verifică dacă bateria poate reține surplusul pentru seară.</p><button type="button" @click="navigate('battery')">Vezi bateria</button></div></article>
        </section>

        <section class="dashboard-card dashboard-components-card">
            <div class="dashboard-card-head"><div><span>Configurație</span><h3>Componente sistem</h3></div><button type="button" @click="navigate('system')">Configurare →</button></div>
            <div class="dashboard-component-list">
                <div><span class="is-solar">☀</span><p><b>Panouri solare</b><small><span x-text="enabledPanelCount"></span> active · <span x-text="(installedW/1000).toFixed(2)"></span> kWp</small></p><em>Activ</em></div>
                <div><span class="is-violet">◈</span><p><b>Invertor</b><small x-text="initial.inverter.name"></small></p><em>Online</em></div>
                <div><span class="is-green">▣</span><p><b>Baterie</b><small x-text="initial.battery.enabled ? Number(initial.battery.capacity_kwh).toFixed(2)+' kWh' : 'Neinstalată'"></small></p><em :class="!initial.battery.enabled && 'is-muted'" x-text="initial.battery.enabled ? 'Pregătită' : 'Opțională'"></em></div>
                <div><span class="is-blue">⌁</span><p><b>Rețea</b><small x-text="gridMode==='prosumer' ? 'Prosumator activ' : gridMode"></small></p><em>Conectată</em></div>
            </div>
        </section>
    </div>
</section>
