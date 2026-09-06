<section id="battery" class="solar-card p-5">
    <div class="card-heading">
        <div><h2>Baterii</h2><p>Stocarea energiei produse în exces</p></div>
        <span x-show="initial.battery.enabled" class="status-online"><i></i> Instalată</span>
    </div>

    <div x-show="initial.battery.enabled" x-cloak>
        <div class="battery-selected">
            <span class="battery-large-icon">🔋</span>
            <div class="min-w-0 flex-1"><b x-text="initial.battery.name"></b><small><span x-text="initial.battery.chemistry"></span> · <span x-text="Number(initial.battery.voltage).toFixed(1)+' V'"></span></small></div>
            <button type="button" @click="removeBattery" :disabled="batteryBusy" class="battery-remove">Elimină</button>
        </div>
        <div class="battery-capacity-row"><div><small>Capacitate</small><b x-text="Number(initial.battery.capacity_kwh).toFixed(2)+' kWh'"></b></div><div><small>Putere maximă</small><b x-text="formatW(initial.battery.max_discharge_power_w)"></b></div><div><small>Randament</small><b x-text="Number(initial.battery.efficiency).toFixed(0)+'%'"></b></div></div>
        <div class="battery-cost-card">
            <span>RON</span>
            <div class="min-w-0 flex-1">
                <small>Cost estimat baterie</small>
                <b x-text="currentBatteryPrice === null ? 'Preț indisponibil' : formatMoney(currentBatteryPrice)"></b>
                <p x-show="currentBatteryPreset">Preț catalog SolarTech pentru produsul asociat configurației.</p>
                <p x-show="!currentBatteryPreset">Bateria instalată nu are încă un preț asociat în catalog.</p>
            </div>
            <a x-show="currentBatteryPreset?.source_url" :href="currentBatteryPreset?.source_url" target="_blank" rel="noreferrer">Vezi produsul ↗</a>
        </div>
        <div class="battery-soc-head"><span>Nivel încărcare</span><b x-text="Number(result.soc).toFixed(0)+'%'"></b></div>
        <div class="battery-progress"><i :style="`width:${result.soc}%`"></i></div>
        <div class="mt-4 grid grid-cols-3 gap-3 text-center"><div class="soft-stat"><small>Independență</small><b x-text="result.metrics.independence+'%'"></b></div><div class="soft-stat"><small>Utilizare solară</small><b x-text="result.metrics.solar_utilization+'%'"></b></div><div class="soft-stat"><small>Stare</small><b x-text="batteryDirection"></b></div></div>
    </div>

    <div x-show="!initial.battery.enabled" x-cloak>
        <div class="battery-empty"><span>▣</span><div><b>Sistemul nu include baterii</b><p>Alege o capacitate pentru a stoca surplusul solar și a reduce importul din rețea.</p></div></div>
        <div class="consumer-section-title"><span>Opțiuni disponibile</span><small x-text="batteryPresets.length+' configurații'"></small></div>
        <div class="battery-catalog">
            <template x-for="preset in batteryPresets" :key="preset.key">
                <button type="button" @click="addBattery(preset)" :disabled="batteryBusy" class="battery-preset">
                    <span class="battery-option-icon" x-text="preset.icon"></span>
                    <span class="min-w-0 flex-1"><b x-text="preset.name"></b><small><span x-text="Number(preset.capacity_kwh).toFixed(2)+' kWh'"></span> · <span x-text="formatW(preset.power_w)"></span> · <span x-text="preset.cycles+' cicluri'"></span></small><em><span x-text="preset.ip_rating"></span> · <span x-text="Number(preset.price_lei).toLocaleString('ro-RO')+' lei'"></span></em></span>
                    <i>＋</i>
                </button>
            </template>
        </div>
    </div>
</section>
