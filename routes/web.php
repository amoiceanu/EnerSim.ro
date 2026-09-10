<?php

use App\Http\Controllers\FeedImportController;
use App\Http\Controllers\AdminProjectController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\SimulationController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/admin', [AdminProjectController::class, 'index'])->middleware(['auth', 'admin'])->name('admin.dashboard');
Route::get('/admin/projects', [AdminProjectController::class, 'index'])->middleware(['auth', 'admin'])->name('admin.projects.index');

Route::resource('projects', ProjectController::class)->only(['index', 'create', 'store', 'show']);
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->middleware(['auth', 'admin'])->name('projects.destroy');
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
