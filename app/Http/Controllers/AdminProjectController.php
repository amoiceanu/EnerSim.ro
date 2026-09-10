<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class AdminProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->withCount('systems')
            ->latest()
            ->paginate(30);

        $summary = [
            'projects' => Project::count(),
            'today' => Project::query()->whereDate('created_at', today())->count(),
            'ip_groups' => Project::query()->whereNotNull('owner_ip_hash')->distinct('owner_ip_hash')->count('owner_ip_hash'),
        ];

        return view('admin.projects', compact('projects', 'summary'));
    }
}
