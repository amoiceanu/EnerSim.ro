<script>
window.projectWizard = () => ({
    step: 1, submitting: false,
    counties: ['Alba','Arad','Argeș','Bacău','Bihor','Brașov','București','Cluj','Constanța','Covasna','Dolj','Galați','Harghita','Iași','Ilfov','Maramureș','Mureș','Prahova','Sibiu','Suceava','Timiș','Vâlcea'],
    project: {name:'',county:'',city:'',latitude:'',longitude:'',notes:''},
    system: {name:'Sistem fotovoltaic principal',panelCount:6,panelPower:505,orientation:'S',tilt:35,losses:15,inverterName:'Deye Hybrid 5 kW',inverterPower:5000,battery:true,batteryCapacity:5.12,batteryPower:2500,soc:80},
    presets: [
        {name:'🧊 Frigider',power:120,startup:700,hours:8,priority:1,essential:true}, {name:'❄️ Ladă frigorifică',power:150,startup:800,hours:8,priority:2,essential:false},
        {name:'💡 Iluminat LED',power:100,startup:0,hours:6,priority:2,essential:true}, {name:'📺 Televizor',power:120,startup:0,hours:4,priority:2,essential:false},
        {name:'📶 Router internet',power:15,startup:0,hours:24,priority:1,essential:true}, {name:'💻 Laptop',power:90,startup:0,hours:6,priority:2,essential:false},
        {name:'🖥️ Desktop PC',power:250,startup:0,hours:6,priority:2,essential:false}, {name:'🧺 Mașină de spălat',power:1800,startup:2100,hours:1,priority:3,essential:false},
        {name:'🧺 Uscător de rufe',power:2200,startup:0,hours:1,priority:3,essential:false}, {name:'🍽️ Mașină de spălat vase',power:1800,startup:0,hours:1.5,priority:3,essential:false},
        {name:'🔥 Plită cu inducție',power:3000,startup:0,hours:1,priority:3,essential:false}, {name:'🍲 Cuptor electric',power:2500,startup:0,hours:1,priority:3,essential:false},
        {name:'☕ Espressor',power:1400,startup:0,hours:.25,priority:3,essential:false}, {name:'🌡️ Boiler electric',power:2000,startup:0,hours:2,priority:3,essential:false},
        {name:'❄️ Aer condiționat',power:1200,startup:1800,hours:5,priority:2,essential:false}, {name:'♨️ Pompă de căldură',power:2500,startup:4500,hours:6,priority:1,essential:true},
        {name:'💧 Pompă de apă',power:900,startup:2500,hours:.5,priority:1,essential:true}, {name:'🪵 Centrală pe lemne',power:180,startup:500,hours:8,priority:1,essential:true},
        {name:'🚗 Mașină electrică',power:7400,startup:0,hours:3,priority:3,essential:false}, {name:'🔌 PHEV',power:3700,startup:0,hours:2,priority:3,essential:false},
        {name:'🛺 Triciclu electric',power:600,startup:0,hours:2,priority:3,essential:false}
    ],
    consumers: [], key: 0,
    init() { this.addPreset(this.presets[0]); this.addPreset(this.presets[2]); },
    addConsumer() { this.consumers.push({key:++this.key,name:'Consumator nou',power:100,startup:0,hours:2,priority:2,essential:false}); },
    addPreset(p) { this.consumers.push({key:++this.key,...p,name:p.name.replace(/^[^\p{L}\p{N}]+\s*/u,'')}); },
    removeConsumer(i) { if(this.consumers.length>1) this.consumers.splice(i,1); },
    get totalPower() { return this.consumers.reduce((s,c)=>s+Number(c.power||0),0); },
    get dailyLoad() { return this.consumers.reduce((s,c)=>s+Number(c.power||0)*Number(c.hours||0)/1000,0); },
    get pvPower() { return Number(this.system.panelCount)*Number(this.system.panelPower); },
    formatKw(w) { return Number(w)>=1000?(Number(w)/1000).toFixed(2)+' kW':Number(w)+' W'; },
    next() { if(this.step===1&&(!this.project.name||!this.project.county||!this.project.city)){alert('Completează numele, județul și localitatea.');return;} if(this.step===2&&!this.consumers.length){alert('Adaugă cel puțin un consumator.');return;} this.step++; }
});
</script>

<style>
    /* EPS is a backup-power designation; expose its meaning without crowding each row. */
    label:has(input[name$="[is_essential]"]) { position: relative; }
    label:has(input[name$="[is_essential]"])::after {
        content: 'Consumator esențial (EPS) · rămâne prioritar în simulare și în alimentarea de backup.';
        position: absolute;
        right: 0;
        bottom: calc(100% + .5rem);
        z-index: 30;
        width: 15rem;
        padding: .7rem .8rem;
        border: 1px solid #bae6fd;
        border-radius: .75rem;
        background: #fff;
        color: #334155;
        font-size: .75rem;
        font-weight: 500;
        line-height: 1.35;
        box-shadow: 0 10px 24px rgb(15 23 42 / 16%);
        opacity: 0;
        pointer-events: none;
        transform: translateY(.25rem);
        transition: opacity .16s ease, transform .16s ease;
    }
    label:has(input[name$="[is_essential]"]):hover::after,
    label:has(input[name$="[is_essential]"]):focus-within::after {
        opacity: 1;
        transform: translateY(0);
    }
</style>
