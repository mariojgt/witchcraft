<?php

use Illuminate\Support\Facades\Route;
use Mariojgt\Witchcraft\Controllers\HomeContoller;
use Mariojgt\Witchcraft\Controllers\NodeController;
use Mariojgt\Witchcraft\Controllers\Node\ApiController;
use Mariojgt\Witchcraft\Controllers\FlowDiagramController;
use Mariojgt\Witchcraft\Controllers\Node\ModelSelectController;

// Standard
Route::group([
    'middleware' => config('witchcraft.editor_middlewares')
], function () {
    // witchcraft
    Route::get('/witchcraft', [HomeContoller::class, 'index'])->name('witchcraft');
});

// Standard
Route::group([
    'middleware' => config('witchcraft.editor_middlewares'),
    'prefix'     => 'api',
], function () {
    Route::apiResource('flow-diagrams', FlowDiagramController::class);
    Route::post('flow-diagrams/{flowDiagram}/execute', [FlowDiagramController::class, 'execute']);
    Route::post('/process-node', [FlowDiagramController::class, 'processNode']);

    Route::get('/models', [ModelSelectController::class, 'getModels']);
    Route::get('/models/{modelName}/fields', [ModelSelectController::class, 'getFields']);
    Route::get('/witchcraft/nodes', [NodeController::class, 'getAvailableNodes']);
});
