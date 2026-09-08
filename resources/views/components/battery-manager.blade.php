<section id="battery" class="solar-card battery-configuration-card p-5">
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
        <div class="battery-empty battery-warning"><span aria-hidden="true">⚠</span><div><b>Sistemul nu include baterii</b><p>Alege o capacitate pentru a stoca surplusul solar și a reduce importul din rețea.</p></div></div>
    </div>

    <div class="consumer-section-title"><span x-text="initial.battery.enabled ? 'Schimbă bateria' : 'Opțiuni disponibile'"></span><small x-text="filteredBatteryPresets.length+' din '+batteryPresets.length+' configurații'"></small></div>
    <section class="battery-filter-panel" aria-label="Filtre baterii">
        <div class="battery-filter-search"><label for="battery-search">Caută baterie</label><input id="battery-search" type="search" x-model="batterySearch" placeholder="Nume, brand sau chimie"></div>
        <label>Capacitate min. (kWh)<input type="number" min="0" step="0.1" x-model.number="batteryCapacityMin" placeholder="Oricare"></label>
        <label>Capacitate max. (kWh)<input type="number" min="0" step="0.1" x-model.number="batteryCapacityMax" placeholder="Oricare"></label>
        <label>Putere min. (W)<input type="number" min="0" x-model.number="batteryPowerMin" placeholder="Oricare"></label>
        <label>Chimie<select x-model="batteryChemistry"><option value="all">Toate</option><template x-for="chemistry in [...new Set(batteryPresets.map(item => item.chemistry))].filter(Boolean)" :key="chemistry"><option :value="chemistry" x-text="chemistry"></option></template></select></label>
        <label>Tensiune<select x-model="batteryVoltage"><option value="all">Toate</option><template x-for="voltage in [...new Set(batteryPresets.map(item => item.voltage))].filter(Boolean).sort((a,b)=>a-b)" :key="voltage"><option :value="voltage" x-text="voltage+' V'"></option></template></select></label>
        <label>Disponibilitate<select x-model="batteryStock"><option value="all">Oricare</option><option value="in_stock">În stoc</option><option value="on_order">La comandă</option></select></label>
        <button type="button" @click="clearBatteryFilters()">Resetează filtrele</button>
    </section>
    <div class="battery-catalog">
        <template x-for="preset in filteredBatteryPresets" :key="preset.key">
            <button type="button" @click="addBattery(preset)" :disabled="batteryBusy" class="battery-preset">
                <span class="battery-option-icon" x-text="preset.icon"></span>
                <span class="min-w-0 flex-1"><b x-text="preset.name"></b><small><span x-text="Number(preset.capacity_kwh).toFixed(2)+' kWh'"></span> · <span x-text="formatW(preset.power_w)"></span> · <span x-text="preset.cycles+' cicluri'"></span></small><em><span x-text="preset.ip_rating"></span> · <span x-text="Number(preset.price_lei).toLocaleString('ro-RO')+' lei'"></span></em></span>
                <i>＋</i>
            </button>
        </template>
    </div>
    <p class="battery-filter-empty" x-show="!filteredBatteryPresets.length">Nu am găsit baterii care să corespundă filtrelor selectate.</p>
</section>
