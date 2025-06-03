<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentationController;

Route::get('/', function () {
    return view('welcome');
});

// Documentation routes
Route::get('/docs', [DocumentationController::class, 'index'])->name('documentation.index');
Route::get('/docs/{filename}', [DocumentationController::class, 'show'])->name('documentation.show');
