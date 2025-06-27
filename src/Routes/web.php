<?php

use Illuminate\Support\Facades\Route;
use Mariojgt\Witchcraft\Controllers\HomeController;
use Mariojgt\Witchcraft\Controllers\FlowDiagramController;
use Mariojgt\Witchcraft\Controllers\Node\ModelUtilityController;

// Main editor route
Route::middleware(config('witchcraft.editor_middlewares', ['web']))
    ->group(function () {
        Route::get('/witchcraft', [HomeController::class, 'index'])->name('witchcraft');
    });

// API routes
Route::middleware(config('witchcraft.editor_middlewares', ['web']))
    ->prefix('api/witchcraft')
    ->group(function () {

        // Flow diagram CRUD
        Route::apiResource('diagrams', FlowDiagramController::class);

        // Enhanced flow endpoints
        Route::get('diagrams-for-selection', [FlowDiagramController::class, 'forSelection']);
        Route::get('flow-statistics', [FlowDiagramController::class, 'statistics']);
        Route::get('categories', [FlowDiagramController::class, 'categories']);
        Route::get('available-icons', [FlowDiagramController::class, 'availableIcons']);

        // Version management
        Route::get('diagrams/{diagram}/versions', [FlowDiagramController::class, 'versions']);
        Route::post('diagrams/{diagram}/restore', [FlowDiagramController::class, 'restore']);

        // Simulation history
        Route::get('diagrams/{diagram}/simulation-runs', [FlowDiagramController::class, 'simulationRuns']);
        Route::post('diagrams/{diagram}/simulation-runs', [FlowDiagramController::class, 'storeSimulationRun']);

        // Flow execution endpoints
        Route::post('diagrams/{diagram}/execute', [FlowDiagramController::class, 'execute']);
        Route::post('execute-flow/{flowId}', [FlowDiagramController::class, 'executeById']);
        Route::post('execute-trigger/{triggerCode}', [FlowDiagramController::class, 'executeByTriggerCode']);
        Route::post('simulate-node', [FlowDiagramController::class, 'simulateNode']);

        // Model utilities
        Route::get('tables', [ModelUtilityController::class, 'getTables']);
        Route::get('tables/{table}/columns', [ModelUtilityController::class, 'getTableColumns']);
        Route::get('tables/{tableName}/records/{id}', [ModelUtilityController::class, 'getRecord']);
    });
