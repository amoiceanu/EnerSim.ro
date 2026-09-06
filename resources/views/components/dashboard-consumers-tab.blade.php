<section x-show="dashboardTab==='consumers'" x-cloak class="dashboard-consumers-tab">
    <div class="consumer-overview-stats">
        <div><span class="bg-violet-50 text-violet-600">ϟ</span><p><small>Consumatori activi</small><b x-text="enabledConsumerUnitCount"></b></p></div>
        <div><span class="bg-blue-50 text-blue-600">∿</span><p><small>Putere conectată</small><b x-text="formatW(enabledConsumerPower)"></b></p></div>
        <div><span class="bg-amber-50 text-amber-600">◷</span><p><small>Consum estimat zilnic</small><b x-text="estimatedDailyLoad.toFixed(1)+' kWh'"></b></p></div>
    </div>

    <div class="active-consumers-grid">
        <section class="solar-card p-5 sm:p-6">
            <div class="card-heading"><div><h2>Consumatori porniți</h2><p>Distribuția puterii active în sistem</p></div><button type="button" @click="navigate('consumers')" class="report-link-inline">Administrează toți →</button></div>
            <div class="active-consumer-list">
                <template x-for="consumer in enabledConsumers" :key="consumer.id">
                    <article class="active-consumer-item">
                        <span class="consumer-icon" x-text="consumerIcon(consumer)"></span>
                        <div class="active-consumer-info"><div><b><span x-text="consumer.name"></span><span x-show="Number(consumer.quantity || 1)>1" x-text="' × '+consumer.quantity"></span></b><small x-text="formatW(Number(consumer.nominal_power_w)*Number(consumer.quantity || 1))"></small></div><div class="active-consumer-bar"><i :style="`width:${enabledConsumerPower ? Math.max(4,Number(consumer.nominal_power_w)*Number(consumer.quantity || 1)/enabledConsumerPower*100) : 0}%`"></i></div></div>
                        <button type="button" @click="toggleConsumer(consumer)" class="toggle-switch switch-on" :aria-label="'Oprește '+consumer.name"><i></i></button>
                    </article>
                </template>
                <div x-show="!enabledConsumers.length" class="active-consumers-empty"><span>ϟ</span><b>Niciun consumator activ</b><p>Pornește aparatele din pagina Consumatori pentru a le vedea aici.</p><button type="button" @click="navigate('consumers')">Deschide consumatorii</button></div>
            </div>
        </section>
        <aside class="space-y-4"><section class="solar-card p-5"><div class="card-heading"><h2>Rezumat consum</h2><span class="count-badge" x-text="enabledConsumerUnitCount"></span></div><dl class="page-details"><div><dt>Putere instantanee</dt><dd x-text="formatW(result.served_load_w)"></dd></div><div><dt>Necesar nominal</dt><dd x-text="formatW(enabledConsumerPower)"></dd></div><div><dt>Consum acumulat</dt><dd x-text="energy.load.toFixed(2)+' kWh'"></dd></div><div><dt>Acoperire solară</dt><dd x-text="result.metrics.solar_utilization+'%'"></dd></div></dl></section><section class="consumer-insight"><span>✦</span><div><b>Optimizare rapidă</b><p>Oprește temporar consumatorii mari și urmărește cum scade importul din rețea.</p></div></section></aside>
    </div>
</section>
