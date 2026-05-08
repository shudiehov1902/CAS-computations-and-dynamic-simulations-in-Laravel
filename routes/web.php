<?php

use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/cas-console', [PageController::class, 'casConsole'])->name('cas-console');
Route::get('/pendulum', [PageController::class, 'pendulum'])->name('pendulum');
Route::get('/ball-beam', [PageController::class, 'ballBeam'])->name('ball-beam');
Route::get('/logs', [LogController::class, 'page'])->name('logs');
Route::get('/logs/export', [LogController::class, 'webExport'])->name('logs.export');
Route::get('/statistics', [StatisticsController::class, 'page'])->name('statistics');
Route::get('/api-docs', [DocumentationController::class, 'page'])->name('api-docs');
Route::get('/openapi.json', [DocumentationController::class, 'publicOpenApi'])->name('openapi.public');

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch');
