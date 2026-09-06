<div id="consumers" class="consumer-transfer-grid">
    <section class="solar-card consumer-selected-panel" :class="consumerDropActive && 'is-drop-active'" @dragover.prevent="$event.dataTransfer.dropEffect='copy'; consumerDropActive=true" @drop.prevent="dropConsumer($event)">
        <div class="consumer-transfer-head"><div><span class="consumer-transfer-icon is-selected">ϟ</span><div><h2>Consumatori</h2><p>Selectați în proiect</p></div></div><span class="count-badge" x-text="consumerUnitCount"></span></div>
        <div class="consumer-drop-message" aria-hidden="true"><span>↓</span><b>Eliberează aici pentru a adăuga</b></div>
        <p class="consumer-transfer-hint"><span aria-hidden="true">⇠</span> Trage aici aparate din lista „Disponibili”</p>

        <div class="consumer-list selected-consumers">
            <template x-for="consumer in consumers" :key="consumer.id">
                <div class="consumer-row">
                    <span class="consumer-icon" x-text="consumerIcon(consumer)"></span>
                    <div class="min-w-0 flex-1"><b class="truncate" x-text="consumerDisplayName(consumer)"></b><small><span x-text="formatW(consumer.nominal_power_w)+'/buc.'"></span><span x-show="Number(consumer.quantity || 1)>1" x-text="' · '+formatW(Number(consumer.nominal_power_w)*Number(consumer.quantity || 1))+' total'"></span><span x-show="!consumer.enabled"> · oprit</span></small></div>
                    <div class="consumer-quantity" aria-label="Cantitate"><button type="button" @click="updateConsumerQuantity(consumer, Number(consumer.quantity || 1)-1)" :disabled="Number(consumer.quantity || 1)<=1 || consumer.quantityBusy" :aria-label="'Scade cantitatea pentru '+consumer.name">−</button><b x-text="consumer.quantity || 1"></b><button type="button" @click="updateConsumerQuantity(consumer, Number(consumer.quantity || 1)+1)" :disabled="Number(consumer.quantity || 1)>=100 || consumer.quantityBusy" :aria-label="'Crește cantitatea pentru '+consumer.name">＋</button></div>
                    <button type="button" @click="toggleConsumer(consumer)" :class="consumer.enabled ? 'switch-on' : ''" class="toggle-switch" :aria-label="(consumer.enabled ? 'Oprește ' : 'Pornește ')+consumer.name"><i></i></button>
                    <button type="button" @click="removeConsumer(consumer)" class="consumer-remove" :aria-label="'Elimină '+consumer.name" title="Elimină din proiect">×</button>
                </div>
            </template>
            <div x-show="!consumers.length" class="consumer-drop-empty"><span>ϟ</span><b>Trage primul consumator aici</b><p>Sau folosește butonul „Adaugă” din lista alăturată.</p></div>
        </div>

        <div class="consumer-total"><span>Total consum activ</span><b x-text="formatW(enabledConsumerPower)"></b></div>
    </section>

    <section class="solar-card consumer-available-panel">
        <div class="consumer-transfer-head"><div><span class="consumer-transfer-icon is-available">＋</span><div><h2>Disponibili</h2><p>Trage un aparat spre Consumatori</p></div></div><span class="count-badge" x-text="availableConsumers.length"></span></div>
        <label class="consumer-search"><span>⌕</span><input type="search" x-model="consumerSearch" placeholder="Caută un aparat..." aria-label="Caută un consumator disponibil"></label>
        <div class="consumer-catalog consumer-drag-catalog">
            <template x-for="preset in filteredAvailableConsumers" :key="preset.key">
                <article class="consumer-preset consumer-draggable" draggable="true" :class="draggedConsumerKey===preset.key && 'is-dragging'" @dragstart="startConsumerDrag($event, preset)" @dragend="endConsumerDrag">
                    <span class="consumer-drag-handle" aria-hidden="true">⠿</span>
                    <span class="consumer-preset-icon" x-text="preset.icon"></span>
                    <span class="min-w-0 flex-1"><b x-text="preset.name"></b><small x-text="formatW(preset.nominal_power_w)"></small></span>
                    <button type="button" @click="addConsumer(preset)" :disabled="consumerAddingKey===preset.key" :aria-label="'Adaugă '+preset.name"><span aria-hidden="true">＋</span><span>Adaugă</span></button>
                </article>
            </template>
            <div x-show="!filteredAvailableConsumers.length" class="consumer-drop-empty"><span>✓</span><b>Toate aparatele sunt selectate</b><p>Elimină un tip din Consumatori pentru a-l readuce aici.</p></div>
        </div>
        <p class="consumer-accessibility-note">Poți trage aparatele sau poți folosi butonul „Adaugă”.</p>
    </section>
</div>
