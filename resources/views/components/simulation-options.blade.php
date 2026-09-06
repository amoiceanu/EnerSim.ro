<section class="simulation-options-page simulation-selector-page" x-show="activeNav==='simulation'" x-cloak>
    <div class="simulation-options-head">
        <div><span class="simulation-eyebrow">Simulare punctuală</span><h2>Alege momentul simulării</h2><p>Selectează luna, ziua și ora pentru a compara producția fotovoltaică cu necesarul consumatorilor.</p></div>
        <button type="button" @click="navigate('dashboard')" class="secondary-button">← Dashboard</button>
    </div>

    <section class="simulation-selector-card" aria-labelledby="simulation-selector-title">
        <header>
            <div><span aria-hidden="true">◷</span><div><small>Moment analizat</small><h3 id="simulation-selector-title" x-text="simulationDateLabel"></h3></div></div>
            <span class="simulation-live-badge" :class="busy && 'is-loading'"><i></i><span x-text="busy ? 'Se recalculează' : 'Rezultat actualizat'"></span></span>
        </header>

        <div class="simulation-range-grid">
            <div class="simulation-range-control">
                <div class="simulation-range-heading"><label for="simulation-month">Luna</label><output for="simulation-month" x-text="simulationMonthLabel"></output></div>
                <input id="simulation-month" type="range" min="1" max="12" step="1" :value="simulationMonth" :style="`--range-progress:${((simulationMonth - 1) / 11) * 100}%`" :aria-valuetext="simulationMonthLabel" @input="setSimulationSelection('month', $event.target.value)">
                <div class="simulation-range-scale"><span>Ian</span><span>Iun</span><span>Dec</span></div>
            </div>

            <div class="simulation-range-control">
                <div class="simulation-range-heading"><label for="simulation-day">Ziua</label><output for="simulation-day" x-text="simulationDay"></output></div>
                <input id="simulation-day" type="range" min="1" :max="daysInSimulationMonth" step="1" :value="simulationDay" :style="`--range-progress:${((simulationDay - 1) / Math.max(1, daysInSimulationMonth - 1)) * 100}%`" :aria-valuetext="`Ziua ${simulationDay}`" @input="setSimulationSelection('day', $event.target.value)">
                <div class="simulation-range-scale"><span>1</span><span x-text="Math.ceil(daysInSimulationMonth / 2)"></span><span x-text="daysInSimulationMonth"></span></div>
            </div>

            <div class="simulation-range-control">
                <div class="simulation-range-heading"><label for="simulation-hour">Ora</label><output for="simulation-hour" x-text="`${String(simulationHour).padStart(2, '0')}:00`"></output></div>
                <input id="simulation-hour" type="range" min="0" max="23" step="1" :value="simulationHour" :style="`--range-progress:${(simulationHour / 23) * 100}%`" :aria-valuetext="`Ora ${String(simulationHour).padStart(2, '0')}:00`" @input="setSimulationSelection('hour', $event.target.value)">
                <div class="simulation-range-scale"><span>00:00</span><span>12:00</span><span>23:00</span></div>
            </div>
        </div>

        <fieldset class="simulation-weather-selector">
            <legend>Tipul zilei</legend>
            <p>Alege condițiile meteo folosite pentru estimarea producției.</p>
            <div role="radiogroup" aria-label="Tipul zilei">
                <template x-for="option in weatherOptions" :key="option.id">
                    <button type="button" role="radio" :aria-checked="weather===option.id" :class="weather===option.id && 'is-selected'" @click="setSimulationWeather(option.id)">
                        <i class="simulation-weather-check" aria-hidden="true">✓</i>
                        <span class="simulation-weather-icon" aria-hidden="true" x-text="option.icon"></span>
                        <span><b x-text="option.label"></b><small x-text="option.description"></small></span>
                    </button>
                </template>
            </div>
        </fieldset>
    </section>

    <section class="simulation-comparison" aria-live="polite" :aria-busy="busy">
        <div class="simulation-comparison-head">
            <div><span class="simulation-eyebrow">Rezultatul selecției</span><h3>Consum vs producție</h3><p x-text="simulationSelectionLabel"></p></div>
            <span class="simulation-verdict" :class="simulationVerdictTone"><i aria-hidden="true" x-text="simulationVerdictIcon"></i><span x-text="simulationVerdictLabel"></span></span>
        </div>

        <div class="simulation-metric-grid">
            <article class="simulation-metric is-production">
                <div><span aria-hidden="true">☀</span><p><small>Producție solară</small><strong x-text="formatW(result.solar_w)"></strong></p></div>
                <div class="simulation-meter"><i :style="`width:${simulationProductionPercent}%`"></i></div>
            </article>
            <article class="simulation-metric is-consumption">
                <div><span aria-hidden="true">ϟ</span><p><small>Consum activ</small><strong x-text="formatW(result.load_w)"></strong></p></div>
                <div class="simulation-meter"><i :style="`width:${simulationConsumptionPercent}%`"></i></div>
            </article>
        </div>

        <div class="simulation-balance-card">
            <div><small>Balanță instantanee</small><strong x-text="simulationBalanceLabel"></strong></div>
            <div><small>Acoperire directă din solar</small><strong x-text="`${simulationCoverage}%`"></strong></div>
            <p x-text="simulationExplanation"></p>
        </div>
    </section>
</section>
