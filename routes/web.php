<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiPlaygroundController;

Route::get('/', function () {
    return redirect()->route('gemini-playground.index');
});

Route::get('/gemini-playground', [GeminiPlaygroundController::class, 'index'])->name('gemini-playground.index');
Route::post('/gemini-playground/process', [GeminiPlaygroundController::class, 'process'])->name('gemini-playground.process');
