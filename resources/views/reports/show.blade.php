<x-layouts.report :document="$document" :project="$project">
    <x-report.toolbar :project="$project" />

    <div class="report-preview-viewport">
        <main class="report-sheet">
            <x-report.header :project="$project" :document="$document" />
            <x-report.title :document="$document" />
            <x-report.metadata :items="[
                'Proiect' => $project->name,
                'Locație' => $project->city.', '.$project->county,
                'Sistem' => $document['system_name'],
                'Generat' => $document['generated_at'],
            ]" />
            <x-report.kpi-grid :metrics="$document['metrics']" />

            @if (! empty($document['sections']))
                @foreach ($document['sections'] as $index => $section)
                    <section class="report-table-section report-cost-section">
                        <x-report.section-heading
                            :eyebrow="$document['type'] === 'system-cost' ? 'Deviz '.($index + 1) : 'Simulare '.($index + 1)"
                            :title="$section['title']"
                            :aside="$section['aside']"
                        />
                        <x-report.table
                            :columns="$document['columns']"
                            :rows="$section['rows']"
                            :footer-label="$section['footer_label']"
                            :footer-value="$section['footer_value']"
                        />
                    </section>
                @endforeach
            @else
                <section class="report-table-section">
                    <x-report.section-heading eyebrow="Detalii raport" title="Datele proiectului" aside="Configurație curentă" />
                    <x-report.table
                        :columns="$document['columns']"
                        :rows="$document['rows']"
                        :footer-label="$document['footer_label'] ?? null"
                        :footer-value="$document['footer_value'] ?? null"
                    />
                </section>
            @endif

            <x-report.callout variant="info" title="Metodologie și limitări">{{ $document['note'] }}</x-report.callout>
            <x-report.callout :variant="$document['conclusion_variant'] ?? 'success'" title="Concluzie" class="report-conclusion-a4"><strong>{{ $document['conclusion'] }}</strong></x-report.callout>
            <x-report.footer :project="$project" />
        </main>
    </div>
</x-layouts.report>
