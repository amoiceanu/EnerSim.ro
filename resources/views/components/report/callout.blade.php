@props(['variant' => 'info', 'title', 'icon' => null])
@php
    $iconName = $icon ?? match($variant) { 'success' => 'check', 'warning', 'danger' => 'warning', default => 'info' };
@endphp
<aside {{ $attributes->class(['report-callout', 'is-'.$variant]) }}>
    <span><x-report.icon :name="$iconName" /></span>
    <p><b>{{ $title }}</b>{{ $slot }}</p>
</aside>
