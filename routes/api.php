<?php

use App\Http\Controllers\CasController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::post('/cas/execute', [CasController::class, 'execute'])->name('api.cas.execute');
Route::post('/simulations/pendulum', [SimulationController::class, 'pendulum'])->name('api.simulations.pendulum');
Route::post('/simulations/ball-beam', [SimulationController::class, 'ballBeam'])->name('api.simulations.ball-beam');
Route::get('/logs', [LogController::class, 'index'])->name('api.logs.index');
Route::get('/logs/export', [LogController::class, 'export'])->name('api.logs.export');
Route::get('/statistics', [StatisticsController::class, 'index'])->name('api.statistics.index');
Route::get('/statistics/{animation}', [StatisticsController::class, 'show'])->name('api.statistics.show');
Route::get('/openapi', [DocumentationController::class, 'openApi'])->name('api.openapi');
Route::get('/docs/pdf', [DocumentationController::class, 'pdf'])->name('api.docs.pdf');
