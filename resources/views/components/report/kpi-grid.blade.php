@props(['metrics'])
@php
    $icons = ['solar' => 'solar-panel', 'green' => 'bolt', 'energy' => 'bolt', 'blue' => 'table', 'info' => 'table', 'violet' => 'inverter', 'capacity' => 'inverter', 'red' => 'warning', 'danger' => 'warning', 'warning' => 'warning'];
@endphp
<section class="report-kpis" aria-label="Indicatori principali" style="--report-kpi-columns: {{ max(2, min(4, count($metrics))) }}">
    @foreach($metrics as $metric)
        <article class="tone-{{ $metric['tone'] }}">
            <span><x-report.icon :name="$metric['icon'] ?? ($icons[$metric['tone']] ?? 'info')" /></span>
            <div><small>{{ $metric['label'] }}</small><strong>{{ $metric['value'] }}</strong></div>
        </article>
    @endforeach
</section>
