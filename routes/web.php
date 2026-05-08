<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/cas-console', [PageController::class, 'casConsole'])->name('cas-console');
Route::get('/pendulum', [PageController::class, 'pendulum'])->name('pendulum');
Route::get('/ball-beam', [PageController::class, 'ballBeam'])->name('ball-beam');
Route::get('/logs', [LogController::class, 'page'])->name('logs');
Route::get('/logs/export', [LogController::class, 'webExport'])->name('logs.export');
Route::get('/statistics', [PageController::class, 'statistics'])->name('statistics');
Route::get('/api-docs', [PageController::class, 'apiDocs'])->name('api-docs');

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch');
