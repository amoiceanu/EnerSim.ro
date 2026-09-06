@props(['name'])
<svg {{ $attributes->merge(['class' => 'report-icon', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.9', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('download')
            <path d="M12 3v11m0 0 4-4m-4 4-4-4M5 16v3h14v-3" />
            @break
        @case('arrow-left')
            <path d="m10 6-6 6 6 6M4 12h16" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('warning')
            <path d="M12 4 3 20h18L12 4Z" /><path d="M12 9v4m0 3h.01" />
            @break
        @case('solar-panel')
            <path d="M6 10h12l2 9H4l2-9Z" /><path d="M8 10 7 19m9-9 1 9M5 15h14M12 10v9m0 0v2" />
            @break
        @case('bolt')
            <path d="m13 2-8 12h7l-1 8 8-12h-7l1-8Z" />
            @break
        @case('table')
            <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 9h18M9 4v16m6-16v16" />
            @break
        @case('inverter')
            <rect x="5" y="2" width="14" height="20" rx="2" /><path d="M8 6h8v6H8zM9 17h6m-4 2h2" />
            @break
        @case('money')
            <rect x="3" y="5" width="18" height="14" rx="2" /><path d="M7 12h.01M17 12h.01M12 9v6m2-5.5c-.5-.4-1.1-.5-2-.5-1.1 0-2 .6-2 1.5s.9 1.5 2 1.5 2 .6 2 1.5-1 1.5-2 1.5c-.9 0-1.6-.2-2-.6" />
            @break
        @case('battery')
            <rect x="5" y="5" width="14" height="15" rx="2" /><path d="M9 2h6v3m-5 8h4m-2-2v4" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="9" /><path d="M12 11v5m0-8h.01" />
            @break
        @default
            <circle cx="12" cy="12" r="4" /><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
    @endswitch
</svg>
