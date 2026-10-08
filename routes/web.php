<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiPlaygroundController;
use App\Http\Controllers\AudioTestController;

Route::get('/', function () {
    return redirect()->route('gemini-playground.index');
});

// Free-form Playground
Route::get('/gemini-playground', [GeminiPlaygroundController::class, 'index'])->name('gemini-playground.index');
Route::post('/gemini-playground/process', [GeminiPlaygroundController::class, 'process'])->name('gemini-playground.process');

// Audio Tests Suite
Route::get('/gemini-tests', [AudioTestController::class, 'index'])->name('gemini-tests.index');
Route::post('/gemini-tests/process', [AudioTestController::class, 'process'])->name('gemini-tests.process');
