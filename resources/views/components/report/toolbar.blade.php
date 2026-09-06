@props(['project'])
<nav class="report-toolbar" aria-label="Acțiuni raport">
    <a href="{{ route('projects.show', $project) }}#reports"><x-report.icon name="arrow-left" /> Înapoi la rapoarte</a>
    <span>Document A4 · pregătit pentru export</span>
    <button type="button" onclick="window.print()"><x-report.icon name="download" /> Exportă PDF</button>
</nav>
