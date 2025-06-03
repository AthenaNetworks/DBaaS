<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Routes (no middleware required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Routes that require authentication
Route::middleware('api.auth')->group(function () {
    // User profile and API key management
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh-key', [AuthController::class, 'refreshApiKey']);
    });
    
    // User management (admin only)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']); // List all users
        Route::get('/{id}', [UserController::class, 'show']); // Get specific user
        Route::post('/', [UserController::class, 'store']); // Create new user
        Route::put('/{id}', [UserController::class, 'update']); // Update user
        Route::delete('/{id}', [UserController::class, 'destroy']); // Delete user
        Route::post('/{id}/reset-key', [UserController::class, 'resetApiKey']); // Reset API key
    });
    
    // Permission management (admin only for some operations)
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('/{table}', [PermissionController::class, 'show']);
        Route::post('/', [PermissionController::class, 'store']); // Admin only
        Route::delete('/{id}', [PermissionController::class, 'destroy']); // Admin only
    });
    
    // DBaaS Routes
    Route::prefix('db')->group(function () {
        // GET request for SELECT operations
        Route::get('/', [DatabaseController::class, 'select'])->name('db.select');
        
        // POST request for INSERT operations (now also supports SELECT via 'method' parameter)
        Route::post('/', [DatabaseController::class, 'process'])->name('db.process');
        
        // Legacy route - POST request for INSERT operations only
        Route::post('/insert', [DatabaseController::class, 'insert'])->name('db.insert');
        
        // PUT request for UPDATE operations
        Route::put('/', [DatabaseController::class, 'update'])->name('db.update');
        
        // DELETE request for DELETE operations
        Route::delete('/', [DatabaseController::class, 'delete'])->name('db.delete');
    });
});
