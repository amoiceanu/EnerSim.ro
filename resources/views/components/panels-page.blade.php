<section x-show="activeNav==='panels'" x-cloak class="dedicated-page">
    <div class="dedicated-page-head">
        <div><span>Generator fotovoltaic</span><h2>Panourile instalate</h2><p>Adaugă, oprește sau elimină panourile și urmărește puterea disponibilă.</p></div>
        <div class="page-summary-pill"><b x-text="(installedW/1000).toFixed(2)"></b><small>kWp activi</small></div>
    </div>
    <div class="panel-page-grid">
        <section class="solar-card p-5">
            <div class="card-heading"><div><h2>Configurație panouri</h2><p><span x-text="enabledPanelCount"></span> din <span x-text="panels.length"></span> active</p></div><span class="status-online" x-show="panels.length"><i></i> Conectate</span></div>
            <div class="panel-catalog">
                <template x-for="group in panelGroups" :key="group.key">
                    <article class="panel-item" :class="!group.enabledCount && 'is-disabled'">
                        <span class="panel-item-icon">▦</span>
                        <div class="min-w-0 flex-1"><b x-text="group.name"></b><small><span x-text="group.panel.power_w+' W'"></span> · <span x-text="group.panel.orientation+' '+group.panel.tilt+'°'"></span><span x-show="group.enabledCount!==group.quantity"> · <span x-text="group.enabledCount"></span> active</span></small></div>
                        <span class="panel-item-quantity"><b x-text="group.quantity"></b> buc.</span>
                        <div class="panel-item-actions">
                            <button type="button" @click="duplicatePanel(group.panel)" class="panel-duplicate" :disabled="panelBusy" :aria-label="'Adaugă încă un panou de tip '+group.name" title="Adaugă un panou identic"><span aria-hidden="true">＋</span> Adaugă unul</button>
                            <button type="button" @click="togglePanelGroup(group)" :class="group.enabledCount===group.quantity && 'switch-on'" class="toggle-switch" :disabled="panelBusy" :aria-label="(group.enabledCount===group.quantity?'Oprește toate panourile ':'Pornește toate panourile ')+group.name"><i></i></button>
                            <button type="button" @click="removePanel(group.panel, group.name)" class="panel-remove" :disabled="panelBusy" :aria-label="'Elimină un panou '+group.name">×</button>
                        </div>
                    </article>
                </template>
                <div x-show="!panels.length" class="panel-empty"><span>☀</span><b>Nu există panouri instalate</b><p>Alege un model din catalog pentru a începe configurarea.</p></div>
            </div>
        </section>
        <div class="space-y-4">
            <section class="solar-card p-5">
                <div class="card-heading"><div><h2>Adaugă panouri</h2><p>Modele reale din catalogul SolarTech</p></div><label class="panel-quantity"><span>Cantitate</span><input type="number" min="1" max="20" x-model.number="panelQuantity" aria-label="Cantitate panouri"></label></div>
                <div class="panel-preset-list">
                    <template x-for="preset in panelPresets" :key="preset.key">
                        <article class="panel-preset">
                            <span>▦</span>
                            <div><b x-text="preset.name"></b><small><span x-text="preset.technology"></span> · <span x-text="preset.efficiency+'% eficiență'"></span></small><a :href="preset.source_url" target="_blank" rel="noreferrer">Fișă produs ↗</a></div>
                            <strong><span x-text="preset.power_w+' W'"></span><small x-text="Number(preset.price_lei).toLocaleString('ro-RO')+' lei'"></small></strong>
                            <button type="button" @click="addPanels(preset)" :disabled="panelBusy"><span>＋</span> Adaugă</button>
                        </article>
                    </template>
                </div>
            </section>
            <section class="solar-card p-5">
                <div class="card-heading"><h2>Rezumat sistem FV</h2></div>
                <dl class="page-details"><div><dt>Putere totală</dt><dd x-text="(panels.reduce((sum,p)=>sum+Number(p.power_w),0)/1000).toFixed(2)+' kWp'"></dd></div><div><dt>Putere activă</dt><dd x-text="(installedW/1000).toFixed(2)+' kWp'"></dd></div><div><dt>Panouri instalate</dt><dd x-text="panels.length"></dd></div><div><dt>Limită intrare invertor</dt><dd x-text="formatW(initial.inverter.max_pv_power_w)"></dd></div></dl>
                <div class="pv-limit-warning" x-show="installedW > Number(initial.inverter.max_pv_power_w)"><span>!</span><p>Puterea panourilor active depășește limita PV a invertorului.</p></div>
            </section>
            <section class="solar-card p-5"><div class="card-heading"><h2>Invertor asociat</h2></div><div class="inverter-feature"><span>◈</span><div><b x-text="initial.inverter.name"></b><small x-text="formatW(initial.inverter.nominal_power_w)+' nominal'"></small></div></div></section>
        </div>
    </div>
</section>
