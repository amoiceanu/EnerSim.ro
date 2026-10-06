@props(['items'])
<dl class="report-project-meta report-project-meta--columns-{{ max(2, min(4, count($items))) }}">
    @foreach($items as $label => $value)
        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
    @endforeach
</dl>
