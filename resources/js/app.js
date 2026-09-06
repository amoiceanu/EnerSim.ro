import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

Alpine.data('energySimulator', (initial) => {
    // Chart.js instances contain circular references and must stay outside
    // Alpine's reactive proxy; proxying one can overflow the call stack.
    let powerChart = null;

    return {
    initial,
    activeNav: 'dashboard',
    dashboardTab: 'system',
    mobileMenu: false,
    proTipVisible: true,
    nightMode: false,
    running: false,
    busy: false,
    speed: 1,
    timer: null,
    scrubTimer: null,
    chartPeriod: 'Zi',
    time: new Date('2026-08-26T14:30:00'),
    initialTime: new Date('2026-08-26T14:30:00'),
    weather: 'clear',
    randomWeather: false,
    gridMode: 'prosumer',
    gridAvailable: true,
    soc: Number(initial.battery.current_soc),
    activeConsumers: [],
    panels: initial.panels,
    panelPresets: initial.panel_presets || [],
    panelQuantity: 1,
    panelBusy: false,
    consumers: initial.consumers,
    consumerPresets: initial.consumer_presets || [],
    consumerSearch: '',
    draggedConsumerKey: null,
    consumerDropActive: false,
    consumerAddingKey: null,
    batteryPresets: initial.battery_presets || [],
    batteryBusy: false,
    inverterPresets: initial.inverter_presets || [],
    inverterBusy: false,
    simulationYear: 2026,
    simulationMonth: 8,
    simulationDay: 26,
    simulationHour: 14,
    simulationTimer: null,
    tickQueued: false,
    tickQueuedRecord: true,
    monthNames: ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'],
    weatherOptions: [
        { id: 'clear', icon: '☀', label: 'Însorită', description: 'Producție maximă' },
        { id: 'partly_cloudy', icon: '◒', label: 'Parțial înnorată', description: 'Nori temporari' },
        { id: 'cloudy', icon: '☁', label: 'Înnorată', description: 'Producție redusă' },
        { id: 'rain', icon: '≋', label: 'Ploioasă', description: 'Radiație scăzută' },
        { id: 'fog', icon: '◌', label: 'Cu ceață', description: 'Vizibilitate redusă' },
        { id: 'snow', icon: '✣', label: 'Cu ninsoare', description: 'Condiții de iarnă' },
    ],
    selectedReport: 'system-cost',
    reportPresets: [
        { id: 'system-cost', icon: '₿', badge: 'Financiar', name: 'Costul sistemului', description: 'Costul panourilor, invertorului și bateriei pe baza catalogului de echipamente.' },
        { id: 'daily-balance', icon: '▤', badge: 'Zilnic', name: 'Bilanț energetic zilnic', description: 'Producție, consum, import și export pentru ziua simulată.' },
        { id: 'monthly-estimate', icon: '▦', badge: 'Estimare', name: 'Estimare lunară', description: 'Proiecție pe 30 de zile pentru producție și necesarul energetic.' },
        { id: 'independence', icon: '◎', badge: 'Eficiență', name: 'Autoconsum și independență', description: 'Cât din consum este acoperit local și cât depinde de rețea.' },
        { id: 'consumers', icon: 'ϟ', badge: 'Consum', name: 'Analiză consumatori', description: 'Puterea conectată, consumul estimat și aparatele dominante.' },
        { id: 'pv-performance', icon: '☀', badge: 'Solar', name: 'Performanță sistem FV', description: 'Puterea instalată, panourile active și utilizarea capacității.' },
        { id: 'battery-backup', icon: '▣', badge: 'Stocare', name: 'Baterie și backup', description: 'Capacitate, nivel de încărcare și disponibilitate în regim de rezervă.' },
    ],
    result: {
        solar_w: 0, solar_potential_w: 0, load_w: 0, served_load_w: 0,
        battery_w: 0, grid_w: 0, soc: Number(initial.battery.current_soc),
        weather: 'clear', events: [], shed_loads: [],
        metrics: { independence: 0, solar_utilization: 0, grid_dependency: 0, score: '—' },
    },
    logs: [{ level: 'INFO', message: `Proiect inițializat · ${initial.project_name}` }],
    energy: { solar: 0, load: 0, import: 0, export: 0 },

    get installedW() {
        return this.panels.filter((panel) => panel.enabled).reduce((sum, panel) => sum + Number(panel.power_w), 0);
    },
    get enabledPanelCount() { return this.panels.filter((panel) => panel.enabled).length; },
    get panelGroups() {
        const groups = new Map();

        this.panels.forEach((panel) => {
            const preset = this.panelPresets.find((item) => panel.name.includes(item.name))
                || this.panelPresets.find((item) => Number(item.power_w) === Number(panel.power_w));
            const name = preset?.name
                || panel.name.replace(/\s+#\d+$/, '').replace(/^Panou\s+\d+$/i, `Panou ${panel.power_w} W`);
            const key = preset?.key || [name, panel.power_w, panel.orientation, panel.tilt, panel.losses_percent, panel.shading_percent].join('|');
            const group = groups.get(key) || { key, name, items: [], quantity: 0, enabledCount: 0, panel };

            group.items.push(panel);
            group.quantity += 1;
            group.enabledCount += panel.enabled ? 1 : 0;
            group.panel = panel;
            groups.set(key, group);
        });

        return Array.from(groups.values());
    },
    get enabledConsumers() { return this.consumers.filter((consumer) => consumer.enabled); },
    get consumerUnitCount() { return this.consumers.reduce((sum, consumer) => sum + Number(consumer.quantity || 1), 0); },
    get enabledConsumerUnitCount() { return this.enabledConsumers.reduce((sum, consumer) => sum + Number(consumer.quantity || 1), 0); },
    get availableConsumers() {
        const selected = new Set(this.consumers.map((consumer) => consumer.name.toLocaleLowerCase('ro-RO')));
        return this.consumerPresets.filter((preset) => ![preset.name, ...(preset.aliases || [])]
            .some((name) => selected.has(name.toLocaleLowerCase('ro-RO'))));
    },
    get filteredAvailableConsumers() {
        const query = this.consumerSearch.trim().toLocaleLowerCase('ro-RO');
        return query ? this.availableConsumers.filter((preset) => `${preset.name} ${preset.category}`.toLocaleLowerCase('ro-RO').includes(query)) : this.availableConsumers;
    },
    get enabledConsumerPower() { return this.enabledConsumers.reduce((sum, consumer) => sum + Number(consumer.nominal_power_w) * Number(consumer.quantity || 1), 0); },
    get batteryDirection() { return this.result.battery_w > 0 ? 'Încărcare' : (this.result.battery_w < 0 ? 'Descărcare' : 'Standby'); },
    get gridLabel() { return !this.gridAvailable ? 'Offline' : (this.result.grid_w > 0 ? 'Import' : (this.result.grid_w < 0 ? 'Export' : 'Echilibru')); },
    get gridImport() { return Math.max(0, Number(this.result.grid_w)); },
    get gridExport() { return Math.max(0, -Number(this.result.grid_w)); },
    get solarCapacityPercent() { return this.installedW ? Math.min(100, Math.round(this.result.solar_w / this.installedW * 100)) : 0; },
    get irradiance() { return this.installedW ? Math.min(1000, Math.round(this.result.solar_potential_w / this.installedW * 1000)) : 0; },
    get weatherLabel() {
        return ({ clear: 'Însorit', partly_cloudy: 'Parțial noros', cloudy: 'Noros', rain: 'Ploaie', fog: 'Ceață', snow: 'Zăpadă' }[this.result.weather] || this.result.weather);
    },
    get formattedDateTime() { return this.time.toLocaleString('ro-RO', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(' la ', ' • '); },
    get shortDate() { return this.time.toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' }); },
    get clockTime() { return this.time.toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' }); },
    get timeMinutes() { return this.time.getHours() * 60 + this.time.getMinutes(); },
    get tickLabel() { return `${Math.max(1, Math.round(Number(this.speed) * 5))} minute`; },
    get pageTitle() {
        return ({ dashboard: 'Dashboard energetic', panels: 'Panouri Solare', inverters: 'Invertoare', consumers: 'Consumatori', battery: 'Baterii', simulation: 'Simulări', reports: 'Rapoarte', weather: 'Locație & Meteo', system: 'Setări Sistem', feed: 'Import feed', guide: 'Ghid de utilizare', about: 'Despre EnerSim' }[this.activeNav] || 'EnerSim');
    },
    get pageSubtitle() {
        return ({ dashboard: 'Monitorizează producția și consumul în timp real', panels: 'Administrează panourile instalate în proiect', inverters: 'Compară și selectează invertorul sistemului', consumers: 'Selectează și controlează aparatele electrice', battery: 'Configurează stocarea și autonomia energetică', simulation: 'Compară producția și consumul la data și ora alese', reports: 'Analizează performanța proiectului', weather: 'Date geografice și condiții de simulare', system: 'Parametrii invertorului și conexiunii la rețea', feed: 'Actualizează catalogul dintr-un fișier Excel', guide: 'Pași simpli pentru configurare și simulare', about: 'Informații despre platforma de simulare' }[this.activeNav] || 'Simulator fotovoltaic');
    },
    get pageIcon() { return ({ dashboard: '⌂', panels: '☀', inverters: '◈', consumers: 'ϟ', battery: '▣', simulation: '▶', reports: '▤', weather: '⌖', system: '⚙', feed: '⇧', guide: '?', about: 'ⓘ' }[this.activeNav] || '☀'); },
    get currentProTip() {
        return ({
            dashboard: { text: 'Adaugă o baterie pentru a folosi seara surplusul produs în timpul zilei.', target: 'battery', action: 'Vezi bateriile' },
            panels: { text: 'Orientarea spre sud și un unghi apropiat de 30–35° oferă de regulă o producție mai echilibrată.', target: 'weather', action: 'Verifică locația' },
            inverters: { text: 'Păstrează puterea panourilor sub limita maximă PV și verifică puterea necesară consumatorilor simultani.', target: 'panels', action: 'Verifică panourile' },
            consumers: { text: 'Marchează sarcinile esențiale înainte să testezi scenariul de pană de rețea.', target: 'simulation', action: 'Testează backup-ul' },
            battery: { text: 'O baterie mai mare crește autonomia, dar trebuie corelată cu puterea invertorului și consumul zilnic.', target: 'system', action: 'Vezi sistemul' },
            simulation: { text: 'Compară aceeași zi cu și fără rețea pentru a înțelege rolul bateriei.', target: 'reports', action: 'Vezi rapoartele' },
            reports: { text: 'Rulează mai întâi o zi completă pentru ca bilanțul energetic să conțină valori relevante.', target: 'simulation', action: 'Alege scenariul' },
            weather: { text: 'Coordonatele exacte îmbunătățesc estimarea poziției soarelui și a producției.', target: 'dashboard', action: 'Înapoi la dashboard' },
            system: { text: 'Puterea maximă simultană a consumatorilor nu trebuie să depășească puterea invertorului.', target: 'consumers', action: 'Verifică sarcinile' },
            feed: { text: 'Reimportarea aceluiași feed actualizează produsele după SKU, fără să creeze duplicate.', target: 'inverters', action: 'Vezi catalogul' },
            guide: { text: 'Începe cu aparatele reale din locuință, apoi testează scenariile extreme.', target: 'consumers', action: 'Configurează consumul' },
            about: { text: 'EnerSim oferă estimări educaționale; configurația finală trebuie verificată de un specialist.', target: 'guide', action: 'Deschide ghidul' },
        })[this.activeNav];
    },
    get estimatedDailyLoad() {
        return this.enabledConsumers.reduce((sum, consumer) => sum + Number(consumer.nominal_power_w) * Number(consumer.quantity || 1) * Number(consumer.behavior?.hours_per_day || 3) / 1000, 0);
    },
    get estimatedDailySolar() { return this.installedW / 1000 * 3.7 * .85; },
    get daysInSimulationMonth() { return new Date(this.simulationYear, this.simulationMonth, 0).getDate(); },
    get simulationMonthLabel() { return this.monthNames[this.simulationMonth - 1]; },
    get simulationDateLabel() { return `${this.simulationDay} ${this.simulationMonthLabel} ${this.simulationYear} · ${String(this.simulationHour).padStart(2, '0')}:00`; },
    get selectedWeatherOption() { return this.weatherOptions.find((option) => option.id === this.weather) || this.weatherOptions[0]; },
    get simulationSelectionLabel() { return `${this.simulationDateLabel} · ${this.selectedWeatherOption.label}`; },
    get simulationBalanceW() { return Number(this.result.solar_w || 0) - Number(this.result.load_w || 0); },
    get simulationCoverage() {
        const load = Number(this.result.load_w || 0);
        return load > 0 ? Math.min(100, Math.round(Number(this.result.solar_w || 0) / load * 100)) : 100;
    },
    get simulationComparisonMax() { return Math.max(1, Number(this.result.solar_w || 0), Number(this.result.load_w || 0)); },
    get simulationProductionPercent() { return Math.round(Number(this.result.solar_w || 0) / this.simulationComparisonMax * 100); },
    get simulationConsumptionPercent() { return Math.round(Number(this.result.load_w || 0) / this.simulationComparisonMax * 100); },
    get simulationVerdictTone() { return this.simulationBalanceW >= 0 ? 'is-positive' : 'is-negative'; },
    get simulationVerdictIcon() { return this.simulationBalanceW >= 0 ? '✓' : '!'; },
    get simulationVerdictLabel() { return this.simulationBalanceW >= 0 ? 'Producția acoperă consumul' : 'Producția nu acoperă consumul'; },
    get simulationBalanceLabel() { return this.simulationBalanceW >= 0 ? `Surplus ${this.formatW(this.simulationBalanceW)}` : `Deficit ${this.formatW(this.simulationBalanceW)}`; },
    get simulationExplanation() {
        if (Number(this.result.load_w || 0) === 0) return 'Nu există consum activ la momentul selectat.';
        return this.simulationBalanceW >= 0
            ? `Panourile produc suficient pentru consumatorii activi, cu un surplus instantaneu de ${this.formatW(this.simulationBalanceW)}.`
            : `Pentru consumatorii activi mai sunt necesari ${this.formatW(this.simulationBalanceW)} din baterie sau din rețea.`;
    },
    get currentReport() { return this.reportPresets.find((report) => report.id === this.selectedReport) || null; },
    get currentBatteryPreset() {
        if (!this.initial.battery.enabled) return null;
        return this.batteryPresets.find((item) => this.initial.battery.name === item.name)
            || this.batteryPresets.find((item) => Math.abs(Number(item.capacity_kwh) - Number(this.initial.battery.capacity_kwh)) < .001)
            || null;
    },
    get currentBatteryPrice() {
        return this.currentBatteryPreset ? Number(this.currentBatteryPreset.price_lei) : null;
    },
    get currentInverterPreset() {
        return this.inverterPresets.find((item) => this.initial.inverter.name.includes(item.model) || this.initial.inverter.name === item.name)
            || this.inverterPresets.find((item) => Number(item.nominal_power_w) === Number(this.initial.inverter.nominal_power_w))
            || null;
    },
    get auxiliaryCostLines() {
        const panelCount = Math.max(1, this.panels.length);
        const stringCount = Math.max(1, Math.ceil(panelCount / 10));
        const dcCableMeters = Math.max(20, Math.ceil(panelCount * 3.5 / 5) * 5);
        const isThreePhase = Number(this.currentInverterPreset?.phases || 1) === 3;
        const lines = [];
        const add = (type, name, specification, quantity, unit, unitPrice) => lines.push({
            type, name, specification, quantity, unit, unit_price: unitPrice,
            total: quantity * unitPrice, source_url: null, estimated: true,
        });

        add('Cabluri', 'Cablu solar DC H1Z2Z2-K 6 mm²', `${stringCount} ${stringCount === 1 ? 'string estimat' : 'stringuri estimate'} · traseu panouri–invertor`, dcCableMeters, 'm', 8.5);
        add('Cabluri', isThreePhase ? 'Cablu AC cupru 5×6 mm²' : 'Cablu AC cupru 3×6 mm²', `${isThreePhase ? 'trifazat' : 'monofazat'} · traseu invertor–tablou`, 15, 'm', isThreePhase ? 52 : 34);
        add('Conectori', 'Set conectori compatibili MC4', 'Perechi pentru stringuri și rezervă de montaj', stringCount * 2, 'perechi', 32);
        add('Protecții DC', 'Tablou protecții fotovoltaice DC', 'Separator DC, SPD tip 2 și siguranțe fuzibile gPV', 1, 'set', 950 + Math.max(0, stringCount - 1) * 180);
        add('Protecții AC', 'Tablou protecții AC', `${isThreePhase ? 'Trifazat' : 'Monofazat'} · disjunctor, diferențial și SPD tip 2`, 1, 'set', isThreePhase ? 1450 : 950);
        add('Împământare', 'Conductor cupru pentru echipotențializare 16 mm²', 'Legături rame, invertor și bara principală', 20, 'm', 22);
        add('Împământare', 'Kit priză de pământ și conexiuni', 'Electrod, cleme și accesorii de legătură', 1, 'set', 550);
        add('Structură', 'Structură aluminiu și cleme pentru panouri', 'Șine, cleme intermediare și cleme terminale', panelCount, 'panouri', 260);
        add('Accesorii', 'Consumabile, etichete și canal de cablu', 'Papuci, presetupe, coliere UV și marcare circuite', 1, 'lot', 350 + panelCount * 15);
        if (this.initial.battery.enabled) add('Protecții baterie', 'Kit cabluri și protecție baterie', 'Cabluri dimensionate, papuci și separator/siguranță DC', 1, 'set', 850);

        return lines;
    },
    get systemCostLines() {
        const lines = [];
        const groups = new Map();
        this.panels.forEach((panel) => {
            const preset = this.panelPresets.find((item) => panel.name.includes(item.name))
                || this.panelPresets.find((item) => Number(item.power_w) === Number(panel.power_w));
            const key = preset?.key || `panel-${panel.power_w}`;
            const current = groups.get(key) || { type: 'Panouri', name: preset?.name || `Panou ${panel.power_w} W`, quantity: 0, unit_price: preset ? Number(preset.price_lei) : null, source_url: preset?.source_url || null };
            current.quantity += 1;
            groups.set(key, current);
        });
        groups.forEach((line) => lines.push({ ...line, total: line.unit_price === null ? null : line.unit_price * line.quantity }));

        const inverter = this.currentInverterPreset;
        lines.push({ type: 'Invertor', name: inverter?.name || this.initial.inverter.name, quantity: 1, unit_price: inverter ? Number(inverter.price_lei) : null, total: inverter ? Number(inverter.price_lei) : null, source_url: inverter?.source_url || null });

        if (this.initial.battery.enabled) {
            const battery = this.currentBatteryPreset;
            lines.push({ type: 'Baterie', name: battery?.name || this.initial.battery.name, quantity: 1, unit_price: battery ? Number(battery.price_lei) : null, total: battery ? Number(battery.price_lei) : null, source_url: battery?.source_url || null });
        }
        return [...lines, ...this.auxiliaryCostLines];
    },
    get systemCostTotal() { return this.systemCostLines.reduce((sum, line) => sum + (line.total ?? 0), 0); },
    get systemCostUnpricedCount() { return this.systemCostLines.filter((line) => line.total === null).length; },
    get panelEquipmentCost() { return this.systemCostLines.filter((line) => line.type === 'Panouri').reduce((sum, line) => sum + (line.total ?? 0), 0); },
    get batteryEquipmentCost() { return this.systemCostLines.filter((line) => line.type === 'Baterie').reduce((sum, line) => sum + (line.total ?? 0), 0); },
    get primaryEquipmentCost() { return this.systemCostLines.filter((line) => !line.estimated).reduce((sum, line) => sum + (line.total ?? 0), 0); },
    get auxiliaryMaterialsCost() { return this.auxiliaryCostLines.reduce((sum, line) => sum + line.total, 0); },
    get cableAndConnectorCost() { return this.auxiliaryCostLines.filter((line) => ['Cabluri', 'Conectori'].includes(line.type)).reduce((sum, line) => sum + line.total, 0); },
    get protectionAndMountingCost() { return this.auxiliaryCostLines.filter((line) => !['Cabluri', 'Conectori'].includes(line.type)).reduce((sum, line) => sum + line.total, 0); },
    formatMoney(value) { return `${Number(value || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} lei`; },
    get reportMetrics() {
        const highest = [...this.enabledConsumers].sort((a, b) => Number(b.nominal_power_w) * Number(b.quantity || 1) - Number(a.nominal_power_w) * Number(a.quantity || 1))[0];
        const coverage = this.estimatedDailyLoad ? Math.min(100, Math.round(this.estimatedDailySolar / this.estimatedDailyLoad * 100)) : 100;
        const reports = {
            'system-cost': [
                { label: 'Cost total estimat', value: this.formatMoney(this.systemCostTotal), tone: 'violet' },
                { label: 'Echipamente principale', value: this.formatMoney(this.primaryEquipmentCost), tone: 'solar' },
                { label: 'Cabluri și conectori', value: this.formatMoney(this.cableAndConnectorCost), tone: 'blue' },
                { label: 'Protecții, structură și accesorii', value: this.formatMoney(this.protectionAndMountingCost), tone: 'green' },
            ],
            'daily-balance': [
                { label: 'Producție acumulată', value: `${this.energy.solar.toFixed(2)} kWh`, tone: 'solar' },
                { label: 'Consum acumulat', value: `${this.energy.load.toFixed(2)} kWh`, tone: 'blue' },
                { label: 'Import rețea', value: `${this.energy.import.toFixed(2)} kWh`, tone: 'violet' },
                { label: 'Export rețea', value: `${this.energy.export.toFixed(2)} kWh`, tone: 'green' },
            ],
            'monthly-estimate': [
                { label: 'Producție estimată', value: `${(this.estimatedDailySolar * 30).toFixed(0)} kWh`, tone: 'solar' },
                { label: 'Consum estimat', value: `${(this.estimatedDailyLoad * 30).toFixed(0)} kWh`, tone: 'blue' },
                { label: 'Acoperire solară', value: `${coverage}%`, tone: 'green' },
                { label: 'Putere instalată', value: `${(this.installedW / 1000).toFixed(2)} kWp`, tone: 'violet' },
            ],
            independence: [
                { label: 'Independență', value: `${this.result.metrics.independence}%`, tone: 'green' },
                { label: 'Utilizare solară', value: `${this.result.metrics.solar_utilization}%`, tone: 'solar' },
                { label: 'Dependență rețea', value: `${this.result.metrics.grid_dependency}%`, tone: 'blue' },
                { label: 'Scor sistem', value: this.result.metrics.score, tone: 'violet' },
            ],
            consumers: [
                { label: 'Consumatori activi', value: `${this.enabledConsumerUnitCount}`, tone: 'violet' },
                { label: 'Putere conectată', value: this.formatW(this.enabledConsumerPower), tone: 'blue' },
                { label: 'Consum zilnic estimat', value: `${this.estimatedDailyLoad.toFixed(1)} kWh`, tone: 'solar' },
                { label: 'Consumator dominant', value: highest?.name || '—', tone: 'green' },
            ],
            'pv-performance': [
                { label: 'Putere instalată', value: `${(this.installedW / 1000).toFixed(2)} kWp`, tone: 'solar' },
                { label: 'Panouri active', value: `${this.enabledPanelCount}/${this.panels.length}`, tone: 'green' },
                { label: 'Producție instantanee', value: this.formatW(this.result.solar_w), tone: 'blue' },
                { label: 'Utilizare capacitate', value: `${this.solarCapacityPercent}%`, tone: 'violet' },
            ],
            'battery-backup': [
                { label: 'Baterie instalată', value: this.initial.battery.enabled ? 'Da' : 'Nu', tone: 'green' },
                { label: 'Capacitate', value: this.initial.battery.enabled ? `${Number(this.initial.battery.capacity_kwh).toFixed(2)} kWh` : '—', tone: 'violet' },
                { label: 'Nivel încărcare', value: this.initial.battery.enabled ? `${Number(this.result.soc).toFixed(0)}%` : '—', tone: 'solar' },
                { label: 'Stare', value: this.initial.battery.enabled ? this.batteryDirection : 'Indisponibilă', tone: 'blue' },
            ],
        };
        return reports[this.selectedReport] || [];
    },

    formatW(value) {
        const watts = Math.abs(Number(value) || 0);
        return watts >= 1000 ? `${(watts / 1000).toFixed(2)} kW` : `${Math.round(watts)} W`;
    },
    consumerIcon(consumer) {
        if (consumer.icon) return consumer.icon;
        const name = consumer.name;
        const normalized = name.toLowerCase();
        if (normalized.includes('mașină electric') || normalized.includes('masina electric')) return '🚗';
        if (normalized.includes('ladă') || normalized.includes('lada')) return '🧊';
        if (normalized.includes('pompă de căldură') || normalized.includes('pompa de caldura')) return '♨️';
        if (normalized.includes('aer condi')) return '🌬️';
        if (normalized.includes('televiz')) return '📺';
        if (normalized.includes('calculator')) return '🖥️';
        if (normalized.includes('cuptor')) return '♨';
        if (normalized.includes('plită') || normalized.includes('plita')) return '🍳';
        if (normalized.includes('vase')) return '🍽️';
        if (normalized.includes('uscător') || normalized.includes('uscator')) return '👕';
        if (normalized.includes('hidro') || normalized.includes('pomp')) return '💧';
        if (normalized.includes('central') || normalized.includes('boiler')) return '🔥';
        if (normalized.includes('frigider')) return '❄️';
        if (normalized.includes('ladă')) return '🧊';
        if (normalized.includes('lumin')) return '💡';
        if (normalized.includes('router') || normalized.includes('ups')) return '📶';
        return '⚡';
    },
    consumerDisplayName(consumer) {
        const normalized = consumer.name.toLocaleLowerCase('ro-RO');
        const preset = this.consumerPresets.find((item) => [item.name, ...(item.aliases || [])]
            .some((name) => name.toLocaleLowerCase('ro-RO') === normalized));
        return preset?.name || consumer.name;
    },
    navigate(section) {
        this.activeNav = section;
        this.mobileMenu = false;
        window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${section}`);
        if (section === 'simulation') this.setSimulationSelection('hour', this.simulationHour);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    init() {
        const requestedPage = window.location.hash.slice(1);
        if (['dashboard','panels','inverters','consumers','battery','simulation','reports','weather','system','feed','guide','about'].includes(requestedPage)) this.activeNav = requestedPage;
        window.addEventListener('hashchange', () => {
            const page = window.location.hash.slice(1);
            if (['dashboard','panels','inverters','consumers','battery','simulation','reports','weather','system','feed','guide','about'].includes(page)) {
                this.activeNav = page;
                if (page === 'simulation') this.setSimulationSelection('hour', this.simulationHour);
            }
        });
        if (this.activeNav === 'simulation') {
            this.time = new Date(this.simulationYear, this.simulationMonth - 1, this.simulationDay, this.simulationHour, 0, 0, 0);
        }
        this.$nextTick(() => {
            this.initChart();
            this.tick(this.activeNav !== 'simulation');
        });
    },
    initChart() {
        if (!this.$refs.powerChart) return;
        const nowLine = {
            id: 'nowLine',
            afterDraw: (chart) => {
                if (this.chartPeriod !== 'Zi') return;
                const { ctx, chartArea, scales } = chart;
                const x = scales.x.getPixelForValue(Math.round(this.timeMinutes / 30));
                ctx.save();
                ctx.setLineDash([4, 4]);
                ctx.strokeStyle = '#8b76ef';
                ctx.lineWidth = 1;
                ctx.beginPath(); ctx.moveTo(x, chartArea.top); ctx.lineTo(x, chartArea.bottom); ctx.stroke();
                ctx.setLineDash([]); ctx.fillStyle = '#6c4cf1'; ctx.font = '600 9px Inter';
                ctx.fillText(`Acum ${this.clockTime}`, Math.min(x + 5, chartArea.right - 66), chartArea.top + 10);
                ctx.restore();
            },
        };
        powerChart = new Chart(this.$refs.powerChart, {
            type: 'line',
            data: { labels: [], datasets: [] },
            plugins: [nowLine],
            options: {
                responsive: true, maintainAspectRatio: false, animation: { duration: 220 },
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end', labels: { color: '#69728a', boxWidth: 7, boxHeight: 7, usePointStyle: true, font: { family: 'Inter', size: 9 } } }, tooltip: { backgroundColor: '#17213c', padding: 9, titleFont: { size: 10 }, bodyFont: { size: 9 } } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#a0a6b3', maxTicksLimit: 5, font: { size: 9 } }, border: { display: false } },
                    y: { beginAtZero: true, grid: { color: '#f0f1f4' }, ticks: { color: '#a0a6b3', callback: (value) => `${value} kW`, font: { size: 9 } }, border: { display: false } },
                },
            },
        });
        this.rebuildChart();
    },
    rebuildChart() {
        if (!powerChart) return;
        let labels = [];
        let production = [];
        let consumption = [];
        if (this.chartPeriod === 'Zi') {
            labels = Array.from({ length: 49 }, (_, index) => `${String(Math.floor(index / 2)).padStart(2, '0')}:${index % 2 ? '30' : '00'}`);
            production = labels.map((_, index) => {
                const hour = index / 2;
                return hour > 5.5 && hour < 20.5 ? Number((this.installedW / 1000 * Math.sin(Math.PI * (hour - 5.5) / 15) * .84).toFixed(2)) : 0;
            });
            consumption = labels.map((_, index) => Number((this.enabledConsumerPower / 1000 * (.56 + .16 * Math.sin(index * .7) + (index > 35 ? .18 : 0))).toFixed(2)));
        } else {
            const count = this.chartPeriod === 'Lună' ? 30 : 12;
            labels = Array.from({ length: count }, (_, index) => this.chartPeriod === 'Lună' ? `${index + 1}` : ['Ian','Feb','Mar','Apr','Mai','Iun','Iul','Aug','Sep','Oct','Nov','Dec'][index]);
            production = labels.map((_, index) => Number((this.installedW / 1000 * (this.chartPeriod === 'Lună' ? 3.2 + Math.sin(index * .8) : 2.5 + 1.8 * Math.sin(Math.PI * index / 11))).toFixed(2)));
            consumption = labels.map((_, index) => Number((this.enabledConsumerPower / 1000 * (2.3 + .35 * Math.cos(index * .7))).toFixed(2)));
        }
        powerChart.data.labels = labels;
        powerChart.data.datasets = [
            { label: 'Producție', data: production, borderColor: '#ffaa18', backgroundColor: 'rgba(255,170,24,.12)', fill: true, tension: .4, pointRadius: 0, borderWidth: 2 },
            { label: 'Consum', data: consumption, borderColor: '#4388f7', backgroundColor: 'rgba(67,136,247,.07)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 },
        ];
        powerChart.update();
    },
    setChartPeriod(period) { this.chartPeriod = period; this.rebuildChart(); },
    updateCurrentChartPoint() {
        if (!powerChart || this.chartPeriod !== 'Zi') return;
        const index = Math.min(48, Math.round(this.timeMinutes / 30));
        powerChart.data.datasets[0].data[index] = Number((this.result.solar_w / 1000).toFixed(2));
        powerChart.data.datasets[1].data[index] = Number((this.result.served_load_w / 1000).toFixed(2));
        powerChart.update('none');
    },
    play() {
        if (this.running) return;
        this.running = true;
        this.timer = setInterval(() => {
            this.time = new Date(this.time.getTime() + Number(this.speed) * 5 * 60000);
            if (this.time.getDate() !== this.initialTime.getDate()) this.time.setHours(0, 0, 0, 0);
            this.tick();
        }, 1000);
    },
    pause() { this.running = false; clearInterval(this.timer); this.timer = null; },
    stop() { this.pause(); this.time = new Date(this.initialTime); this.tick(); },
    reset() {
        this.pause();
        this.time = new Date(this.initialTime);
        this.soc = Number(initial.battery.current_soc);
        this.energy = { solar: 0, load: 0, import: 0, export: 0 };
        this.logs = [{ level: 'INFO', message: 'Simulare resetată' }];
        this.rebuildChart();
        this.tick();
    },
    setTimeline(minutes) {
        const value = Number(minutes);
        this.time.setHours(Math.floor(value / 60), value % 60, 0, 0);
        this.time = new Date(this.time);
        clearTimeout(this.scrubTimer);
        this.scrubTimer = setTimeout(() => this.tick(), 100);
    },
    setSimulationSelection(part, value) {
        const selected = Number(value);
        if (part === 'month') {
            this.simulationMonth = selected;
            this.simulationDay = Math.min(this.simulationDay, this.daysInSimulationMonth);
        }
        if (part === 'day') this.simulationDay = selected;
        if (part === 'hour') this.simulationHour = selected;

        this.time = new Date(this.simulationYear, this.simulationMonth - 1, this.simulationDay, this.simulationHour, 0, 0, 0);
        this.soc = Number(this.initial.battery.current_soc);
        clearTimeout(this.simulationTimer);
        this.simulationTimer = setTimeout(() => this.tick(false), 180);
    },
    setSimulationWeather(weather) {
        this.weather = weather;
        this.randomWeather = false;
        clearTimeout(this.simulationTimer);
        this.simulationTimer = setTimeout(() => this.tick(false), 120);
    },
    async tick(recordResult = true) {
        if (this.busy) {
            this.tickQueued = true;
            this.tickQueuedRecord = recordResult;
            return;
        }
        this.busy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/simulation/tick`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: JSON.stringify({ time: this.time.toISOString(), soc: this.soc, weather: this.weather, random_weather: this.randomWeather, grid_mode: this.gridMode, grid_available: this.gridAvailable, active_consumers: this.activeConsumers, tick_minutes: Math.max(1, Math.round(Number(this.speed) * 5)) }),
            });
            if (!response.ok) throw new Error('Actualizarea simulării a eșuat');
            const data = await response.json();
            this.result = data;
            this.soc = Number(data.soc);
            if (recordResult) this.record(data);
        } catch (error) {
            this.logs.unshift({ level: 'CRITICAL', message: error.message });
        } finally {
            this.busy = false;
            if (this.tickQueued) {
                this.tickQueued = false;
                this.tick(this.tickQueuedRecord);
            }
        }
    },
    record(data) {
        const hours = Math.max(1, Number(this.speed) * 5) / 60;
        this.energy.solar += data.solar_w * hours / 1000;
        this.energy.load += data.served_load_w * hours / 1000;
        this.energy.import += Math.max(0, data.grid_w) * hours / 1000;
        this.energy.export += Math.max(0, -data.grid_w) * hours / 1000;
        data.events.forEach((event) => this.logs.unshift(event));
        if (data.shed_loads.length) this.logs.unshift({ level: 'WARNING', message: `Deconectați: ${data.shed_loads.join(', ')}` });
        this.logs = this.logs.slice(0, 20);
        this.updateCurrentChartPoint();
    },
    async toggleConsumer(consumer) {
        const previous = consumer.enabled;
        consumer.enabled = !previous;
        try {
            const response = await fetch(`/projects/${initial.project_id}/consumers/${consumer.id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
            if (!response.ok) throw new Error('Consumatorul nu a putut fi actualizat');
            const data = await response.json();
            consumer.enabled = data.enabled;
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            consumer.enabled = previous;
            this.logs.unshift({ level: 'WARNING', message: error.message });
        }
    },
    startConsumerDrag(event, preset) {
        this.draggedConsumerKey = preset.key;
        this.consumerDropActive = false;
        event.dataTransfer.effectAllowed = 'copy';
        event.dataTransfer.setData('text/plain', preset.key);
    },
    endConsumerDrag() {
        this.draggedConsumerKey = null;
        this.consumerDropActive = false;
    },
    dropConsumer(event) {
        const key = event.dataTransfer.getData('text/plain') || this.draggedConsumerKey;
        const preset = this.availableConsumers.find((item) => item.key === key);
        this.endConsumerDrag();
        if (preset) this.addConsumer(preset);
    },
    async addConsumer(preset) {
        if (this.consumerAddingKey) return;
        this.consumerAddingKey = preset.key;
        try {
            const response = await fetch(`/projects/${initial.project_id}/consumers`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: JSON.stringify({ preset: preset.key }),
            });
            if (!response.ok) throw new Error('Consumatorul nu a putut fi adăugat');
            const consumer = await response.json();
            const existing = this.consumers.find((item) => item.id === consumer.id);
            if (existing) Object.assign(existing, consumer);
            else this.consumers.push(consumer);
            this.consumerSearch = '';
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.consumerAddingKey = null;
        }
    },
    async updateConsumerQuantity(consumer, quantity) {
        const previous = Number(consumer.quantity || 1);
        const next = Math.max(1, Math.min(100, Number(quantity)));
        if (next === previous || consumer.quantityBusy) return;
        consumer.quantity = next;
        consumer.quantityBusy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/consumers/${consumer.id}/quantity`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: JSON.stringify({ quantity: next }),
            });
            if (!response.ok) throw new Error('Cantitatea nu a putut fi actualizată');
            consumer.quantity = Number((await response.json()).quantity);
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            consumer.quantity = previous;
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            consumer.quantityBusy = false;
        }
    },
    async removeConsumer(consumer) {
        if (!window.confirm(`Elimini ${consumer.name} din proiect?`)) return;
        try {
            const response = await fetch(`/projects/${initial.project_id}/consumers/${consumer.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Consumatorul nu a putut fi eliminat');
            this.consumers = this.consumers.filter((item) => item.id !== consumer.id);
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        }
    },
    async addBattery(preset) {
        if (this.batteryBusy) return;
        this.batteryBusy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/battery`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: JSON.stringify({ preset: preset.key }),
            });
            if (!response.ok) throw new Error('Bateria nu a putut fi adăugată');
            this.initial.battery = await response.json();
            this.soc = Number(this.initial.battery.current_soc);
            this.result.soc = this.soc;
            this.logs.unshift({ level: 'INFO', message: `Baterie instalată · ${this.initial.battery.name}` });
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.batteryBusy = false;
        }
    },
    async removeBattery() {
        if (this.batteryBusy || !window.confirm('Elimini bateria din sistem?')) return;
        this.batteryBusy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/battery`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Bateria nu a putut fi eliminată');
            this.initial.battery = await response.json();
            this.result.battery_w = 0;
            this.logs.unshift({ level: 'INFO', message: 'Bateria a fost eliminată din sistem' });
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.batteryBusy = false;
        }
    },
    async selectInverter(preset) {
        if (this.inverterBusy) return;
        this.inverterBusy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/inverter`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: JSON.stringify({ preset: preset.key }),
            });
            if (!response.ok) throw new Error('Invertorul nu a putut fi selectat');
            this.initial.inverter = await response.json();
            this.logs.unshift({ level: 'INFO', message: `Invertor selectat · ${this.initial.inverter.name}` });
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.inverterBusy = false;
        }
    },
    isCurrentInverter(preset) {
        return this.initial.inverter.name === preset.name
            || this.initial.inverter.name.includes(preset.model)
            || Number(this.initial.inverter.nominal_power_w) === Number(preset.nominal_power_w);
    },
    openReport(report) {
        this.selectedReport = report.id;
        this.$nextTick(() => document.getElementById('report-preview')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    },
    async togglePanel(panel) {
        const previous = panel.enabled;
        panel.enabled = !previous;
        try {
            const response = await fetch(`/projects/${initial.project_id}/panels/${panel.id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
            if (!response.ok) throw new Error('Panoul nu a putut fi actualizat');
            const data = await response.json();
            panel.enabled = data.enabled;
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            panel.enabled = previous;
            this.logs.unshift({ level: 'WARNING', message: error.message });
        }
    },
    async togglePanelGroup(group) {
        if (this.panelBusy) return;
        this.panelBusy = true;
        const enable = group.enabledCount < group.quantity;

        try {
            for (const panel of group.items.filter((item) => item.enabled !== enable)) {
                const response = await fetch(`/projects/${initial.project_id}/panels/${panel.id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
                if (!response.ok) throw new Error('Grupul de panouri nu a putut fi actualizat');
                panel.enabled = (await response.json()).enabled;
            }
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.panelBusy = false;
        }
    },
    async addPanels(preset) {
        if (this.panelBusy) return;
        this.panelBusy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/panels`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: JSON.stringify({ preset: preset.key, quantity: Math.max(1, Math.min(20, Number(this.panelQuantity) || 1)) }),
            });
            if (!response.ok) throw new Error('Panourile nu au putut fi adăugate');
            const addedPanels = await response.json();
            this.panels.push(...addedPanels);
            this.logs.unshift({ level: 'INFO', message: `${addedPanels.length} ${addedPanels.length === 1 ? 'panou adăugat' : 'panouri adăugate'} · ${preset.name}` });
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.panelBusy = false;
        }
    },
    async duplicatePanel(panel) {
        if (this.panelBusy) return;
        this.panelBusy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/panels/${panel.id}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Panoul nu a putut fi duplicat');
            const duplicate = await response.json();
            this.panels.push(duplicate);
            this.logs.unshift({ level: 'INFO', message: `Panou duplicat · ${panel.name}` });
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.panelBusy = false;
        }
    },
    async removePanel(panel, displayName = panel.name) {
        if (this.panelBusy || !window.confirm(`Elimini un panou ${displayName} din proiect?`)) return;
        this.panelBusy = true;
        try {
            const response = await fetch(`/projects/${initial.project_id}/panels/${panel.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Panoul nu a putut fi eliminat');
            this.panels = this.panels.filter((item) => item.id !== panel.id);
            this.logs.unshift({ level: 'INFO', message: `Panou eliminat · ${displayName}` });
            this.rebuildChart();
            await this.tick();
        } catch (error) {
            this.logs.unshift({ level: 'WARNING', message: error.message });
        } finally {
            this.panelBusy = false;
        }
    },
    };
});

Alpine.start();
