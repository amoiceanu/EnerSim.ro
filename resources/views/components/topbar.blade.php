@props(['project'])
<header class="solar-topbar">
    <div class="flex items-center gap-3">
        <button type="button" @click="mobileMenu=true" class="icon-button lg:hidden" aria-label="Deschide meniul">☰</button>
        <span class="hidden size-11 place-items-center rounded-xl bg-violet-50 text-2xl text-violet-600 sm:grid" x-text="pageIcon"></span>
        <div><h1 class="text-xl font-bold text-slate-900 sm:text-2xl" x-text="pageTitle"></h1><p class="hidden text-sm text-slate-500 sm:block" x-text="pageSubtitle"></p></div>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="nightMode=!nightMode" class="day-toggle" :title="nightMode ? 'Comută pe mod zi' : 'Comută pe mod noapte'"><span :class="!nightMode && 'active'">☀</span><span :class="nightMode && 'active'">☾</span></button>
        <button type="button" @click="running ? pause() : navigate('simulation')" class="primary-button"><span x-text="running ? 'Ⅱ' : '▶'"></span><span class="hidden sm:inline" x-text="running ? 'Pauză' : 'Simulare'"></span></button>
        <button type="button" @click="window.print()" class="secondary-button"><span>⇩</span><span class="hidden md:inline">Export PDF</span></button>
    </div>
</header>
