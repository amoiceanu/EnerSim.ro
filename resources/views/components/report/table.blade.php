@props(['columns', 'rows', 'empty' => 'Nu există date disponibile pentru acest raport.', 'footerLabel' => null, 'footerValue' => null])
<div class="report-table-scroll">
    <table>
        <thead><tr>@foreach($columns as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                @php($cells = $row['cells'] ?? $row)
                <tr @class([$row['class'] ?? ''])>@foreach($cells as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($columns) }}">{{ $empty }}</td></tr>
            @endforelse
        </tbody>
        @if ($footerLabel !== null && $footerValue !== null)
            <tfoot>
                <tr>
                    <th colspan="{{ max(1, count($columns) - 1) }}" scope="row">{{ $footerLabel }}</th>
                    <th scope="row">{{ $footerValue }}</th>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
