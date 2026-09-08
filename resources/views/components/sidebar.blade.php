@props(['project', 'system', 'assessment'])
<div x-show="mobileMenu" x-transition.opacity @click="mobileMenu=false" class="fixed inset-0 z-40 bg-slate-900/25 backdrop-blur-sm lg:hidden" x-cloak></div>
<aside :class="mobileMenu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="solar-sidebar fixed inset-y-0 left-0 z-50 flex w-[270px] flex-col transition-transform duration-200 lg:translate-x-0">
    <div class="px-5 pb-4 pt-6">
        <a href="{{ route('projects.index') }}" class="flex items-center gap-3">
            <span class="solar-logo">☀</span>
            <span><b class="block text-2xl font-extrabold tracking-tight text-slate-900">Ener<span class="text-violet-600">Sim</span></b><small class="text-xs font-medium text-slate-500">Simulează. Optimizează. Economisește.</small></span>
        </a>
    </div>
    <div class="mx-4 mb-4 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5">
        <span class="block text-[11px] font-bold uppercase tracking-[.14em] text-slate-500">Proiect activ</span>
        <b class="mt-1 block truncate text-sm text-slate-800">{{ $project->name }}</b>
        <span class="block truncate text-xs text-slate-500">{{ $project->city }}, {{ $project->county }}</span>
    </div>
    <nav class="sidebar-scroll flex-1 overflow-y-auto px-3 pb-4">
        @foreach([
            'General' => [['dashboard','⌂','Dashboard'],['panels','▦','Panouri Solare'],['inverters','◈','Invertoare'],['consumers','ϟ','Consumatori'],['battery','▣','Baterii'],['simulation','▶','Simulări'],['reports','▤','Rapoarte']],
            'Configurare' => [['weather','⌖','Locație & Meteo'],['system','⚙','Setări Sistem'],['feed','⇧','Import feed']],
            'Informații' => [['guide','?','Ghid Utilizare'],['about','ⓘ','Despre']],
        ] as $group => $items)
            <p class="sidebar-label">{{ $group }}</p>
            <div class="space-y-1">
                @foreach($items as [$id,$icon,$label])
                    <button type="button" @click="navigate('{{ $id }}')" :class="activeNav==='{{ $id }}' ? 'sidebar-link-active' : ''" class="sidebar-link">
                        <span class="sidebar-icon">{{ $icon }}</span><span>{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        @endforeach
    </nav>
    <div class="border-t border-slate-100 p-4">
    </div>
</aside>
