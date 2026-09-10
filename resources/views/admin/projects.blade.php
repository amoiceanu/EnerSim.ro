@extends('layouts.app', ['title' => 'Administrare proiecte · EnerSim'])

@section('content')
<div class="admin-dashboard-shell">
    <div class="admin-dashboard-nav"><x-public-nav /></div>
<main class="admin-projects-page">
    <header class="admin-projects-header">
        <div>
            <p>ADMINISTRARE</p>
            <h1>Proiecte publice</h1>
            <span>Vizualizare centralizată a proiectelor create fără cont, grupate prin amprenta IP.</span>
        </div>
        <span class="admin-projects-back">Dashboard admin</span>
    </header>

    <section class="admin-projects-summary" aria-label="Sumar proiecte">
        <article><small>Total proiecte</small><strong>{{ $summary['projects'] }}</strong></article>
        <article><small>Create astăzi</small><strong>{{ $summary['today'] }}</strong></article>
        <article><small>Amprente IP active</small><strong>{{ $summary['ip_groups'] }}</strong></article>
    </section>

    <section class="admin-projects-table" aria-labelledby="admin-projects-list-title">
        <div class="admin-projects-table-heading"><div><p>REGISTRU PUBLIC</p><h2 id="admin-projects-list-title">Toate proiectele</h2></div><span>{{ $projects->total() }} înregistrări</span></div>
        <div class="admin-projects-scroll">
            <table>
                <thead><tr><th>Proiect</th><th>Locație</th><th>Sisteme</th><th>Amprentă IP</th><th>Creat</th><th>Acțiuni</th></tr></thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td><strong>{{ $project->name }}</strong><small>{{ $project->share_token }}</small></td>
                            <td>{{ $project->city }}, {{ $project->county }}</td>
                            <td>{{ $project->systems_count }}</td>
                            <td><code>{{ $project->owner_ip_hash ? 'IP · '.substr($project->owner_ip_hash, 0, 12) : 'Proiect existent' }}</code></td>
                            <td>{{ $project->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="admin-project-actions">
                                    <a href="{{ route('projects.show', $project) }}" aria-label="Deschide proiectul {{ $project->name }}">Deschide →</a>
                                    <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Ștergi definitiv proiectul „{{ addslashes($project->name) }}”? Această acțiune nu poate fi anulată.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-project-delete" aria-label="Șterge proiectul {{ $project->name }}">Șterge</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Nu există proiecte publice.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($projects->hasPages())<div class="admin-projects-pagination">{{ $projects->links() }}</div>@endif
    </section>
</main>
    <x-app-footer />
</div>
@endsection
