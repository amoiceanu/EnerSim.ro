<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Reports\ProjectReportService;
use App\Services\Simulation\ProjectAssessmentService;
use Illuminate\View\View;

final class ProjectReportController extends Controller
{
    public function __invoke(Project $project, string $report, ProjectReportService $reports, ProjectAssessmentService $assessment): View
    {
        abort_unless(array_key_exists($report, ProjectReportService::definitions()), 404);

        $project->load(['systems.panels', 'systems.inverter', 'systems.battery', 'systems.consumers']);

        return view('reports.show', [
            'project' => $project,
            'document' => $reports->build($project, $report, $assessment->assess($project)),
        ]);
    }
}
