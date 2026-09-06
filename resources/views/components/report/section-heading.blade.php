@props(['eyebrow', 'title', 'aside' => null])
<div class="report-section-heading">
    <div><span>{{ $eyebrow }}</span><h2>{{ $title }}</h2></div>
    @if($aside)<small>{{ $aside }}</small>@endif
</div>
