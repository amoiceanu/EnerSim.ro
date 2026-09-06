<?php

use App\Http\Controllers\FeedImportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\SimulationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/projects');
Route::resource('projects', ProjectController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::get('/projects/{project}/reports/{report}', ProjectReportController::class)->name('reports.show');
Route::post('/projects/{project}/simulation/tick', [SimulationController::class, 'tick'])->name('simulation.tick');
Route::post('/projects/{project}/consumers', [SimulationController::class, 'storeConsumer'])->name('consumers.store');
Route::patch('/projects/{project}/consumers/{consumer}/toggle', [SimulationController::class, 'toggleConsumer'])->name('consumers.toggle');
Route::patch('/projects/{project}/consumers/{consumer}/quantity', [SimulationController::class, 'updateConsumerQuantity'])->name('consumers.quantity');
Route::delete('/projects/{project}/consumers/{consumer}', [SimulationController::class, 'destroyConsumer'])->name('consumers.destroy');
Route::post('/projects/{project}/battery', [SimulationController::class, 'storeBattery'])->name('battery.store');
Route::delete('/projects/{project}/battery', [SimulationController::class, 'destroyBattery'])->name('battery.destroy');
Route::post('/projects/{project}/panels', [SimulationController::class, 'storePanels'])->name('panels.store');
Route::post('/projects/{project}/panels/{panel}/duplicate', [SimulationController::class, 'duplicatePanel'])->name('panels.duplicate');
Route::patch('/projects/{project}/panels/{panel}/toggle', [SimulationController::class, 'togglePanel'])->name('panels.toggle');
Route::delete('/projects/{project}/panels/{panel}', [SimulationController::class, 'destroyPanel'])->name('panels.destroy');
Route::put('/projects/{project}/inverter', [SimulationController::class, 'storeInverter'])->name('inverter.store');
Route::post('/projects/{project}/feed/import', FeedImportController::class)->name('feed.import');
