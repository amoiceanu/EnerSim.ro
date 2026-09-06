<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImportEquipmentFeedRequest;
use App\Models\Project;
use App\Services\Catalog\EquipmentFeedImporter;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class FeedImportController extends Controller
{
    public function __invoke(ImportEquipmentFeedRequest $request, Project $project, EquipmentFeedImporter $importer): RedirectResponse
    {
        try {
            $result = $importer->import($request->file('feed')->getRealPath());
        } catch (RuntimeException $exception) {
            return redirect(route('projects.show', $project).'#feed')
                ->withErrors(['feed' => $exception->getMessage()]);
        }

        $processed = $result['created'] + $result['updated'];

        return redirect(route('projects.show', $project).'#feed')
            ->with('success', "Feed importat: {$processed} produse procesate, {$result['created']} adăugate și {$result['updated']} actualizate.")
            ->with('feed_import_result', $result);
    }
}
