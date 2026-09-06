@props(['document'])
<section class="report-title-block">
    <span>{{ $document['badge'] }} · {{ strtoupper($document['type']) }}</span>
    <h1>{{ $document['title'] }}</h1>
    <p>{{ $document['description'] }}</p>
</section>
