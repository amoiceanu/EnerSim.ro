<section class="simulation-options-page simulation-selector-page simulation-zoom-page" x-show="activeNav==='simulation'" x-cloak>
    <div class="simulation-options-head">
        <div><span class="simulation-eyebrow">Simulare punctuală</span><h2>Alege momentul simulării</h2><p>Selectează luna, ziua și ora pentru a compara producția fotovoltaică cu necesarul consumatorilor.</p></div>
        <button type="button" @click="navigate('dashboard')" class="secondary-button">← Dashboard</button>
    </div>

    <div class="simulation-workspace-grid">
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

        <article class="simulation-power-comparison" aria-label="Comparație producție solară și consum activ">
            <div class="simulation-power-comparison-head">
                <h4>Producție vs consum</h4>
                <div class="simulation-chart-legend" aria-label="Legendă chart">
                    <span class="is-production"><i></i>Producție solară</span>
                    <span class="is-consumption"><i></i>Consum activ</span>
                </div>
            </div>

            <div class="simulation-comparison-bars">
                <div class="simulation-comparison-bar is-production">
                    <div class="simulation-comparison-bar-label"><span aria-hidden="true">☀</span><b>Producție solară</b></div>
                    <div class="simulation-comparison-track"><i :style="`width:${simulationProductionPercent}%`"></i></div>
                    <strong x-text="formatW(result.solar_w)"></strong>
                </div>
                <div class="simulation-comparison-bar is-consumption">
                    <div class="simulation-comparison-bar-label"><span aria-hidden="true">ϟ</span><b>Consum activ</b></div>
                    <div class="simulation-comparison-track"><i :style="`width:${simulationConsumptionPercent}%`"></i></div>
                    <strong x-text="formatW(result.load_w)"></strong>
                </div>
            </div>

            <p class="simulation-result-line" :class="simulationBalanceW >= 0 ? 'is-surplus' : 'is-deficit'">
                <span x-text="simulationBalanceW >= 0 ? 'Surplus instantaneu:' : 'Deficit instantaneu:'"></span>
                <b x-text="formatW(Math.abs(simulationBalanceW))"></b>
            </p>

            <aside class="simulation-panel-advice" x-show="simulationBalanceW < 0" x-cloak aria-live="polite">
                <span aria-hidden="true">☀</span>
                <div x-show="simulationAdditionalPanelCount > 0">
                    <small>Recomandare de dimensionare</small>
                    <p>Adaugă <b x-text="simulationAdditionalPanelCount"></b> <span x-text="simulationAdditionalPanelCount === 1 ? 'panou' : 'panouri'"></span> de același tip cu cele selectate pentru a acoperi acest deficit.</p>
                    <em x-text="simulationSuggestedPanel ? simulationSuggestedPanel.name+' · '+formatW(simulationSuggestedPanel.power_w) : ''"></em>
                </div>
                <div x-show="simulationAdditionalPanelCount === 0">
                    <small>Recomandare de dimensionare</small>
                    <p>La această oră și stare meteo producția este prea mică pentru o estimare utilă de panouri suplimentare. Alege un moment cu lumină solară.</p>
                </div>
            </aside>

            <aside class="simulation-battery-advice" x-show="simulationBatteryRecommendationNeeded" x-cloak aria-live="polite">
                <div class="simulation-battery-advice-head">
                    <span aria-hidden="true">▣</span>
                    <div>
                        <small>Recomandare de stocare</small>
                        <p x-text="initial.battery.enabled ? 'Bateria actuală are nevoie de o capacitate sau putere de descărcare mai mare pentru acest deficit.' : 'Adaugă o baterie pentru a susține deficitul instantaneu.'"></p>
                    </div>
                    <button type="button" @click="navigate('battery')">Vezi baterii →</button>
                </div>
                <p class="simulation-battery-target">Țintă recomandată: <b x-text="simulationRecommendedBatteryCapacityKwh.toFixed(1)+' kWh'"> </b> de stocare pentru aproximativ 2 ore.</p>
                <div class="simulation-battery-options" x-show="simulationRecommendedBatteries.length">
                    <template x-for="battery in simulationRecommendedBatteries" :key="battery.key">
                        <button type="button" @click="addBattery(battery)" :disabled="batteryBusy" :aria-label="'Adaugă bateria recomandată '+battery.name" title="Adaugă bateria și recalculează simularea">
                            <span x-text="battery.recommendedQuantity+' ×'"></span>
                            <p><b x-text="battery.name"></b><small><span x-text="Number(battery.capacity_kwh).toFixed(1)+' kWh'"></span> · <span x-text="formatW(battery.power_w)"></span></small></p>
                            <i aria-hidden="true">＋</i>
                        </button>
                    </template>
                </div>
                <p class="simulation-battery-empty" x-show="!simulationRecommendedBatteries.length">Verifică bateria din catalog pentru o capacitate de cel puțin <b x-text="simulationRecommendedBatteryCapacityKwh.toFixed(1)+' kWh'"></b> și o putere de descărcare de cel puțin <b x-text="formatW(Math.abs(simulationBalanceW))"></b>.</p>
            </aside>
        </article>
    </section>
    </div>
</section>
