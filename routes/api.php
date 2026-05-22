<?php
use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Cache Metrics
    Route::get('/cache-metrics', [UserController::class, 'cacheMetrics']);
    
    // Notifications
    Route::get('/notifications', [UserController::class, 'notifications']);
    Route::post('/notifications-read-all', [UserController::class, 'markAsReadAll']);

    // Users
    Route::prefix('users')->group(function () {

        Route::middleware('permission:view_users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{id}', [UserController::class, 'show']);
        });

        Route::middleware('permission:update_users')->group(function () {
            Route::put('/{id}', [UserController::class, 'update']);
        });

        Route::middleware('permission:create_users')->group(function () {
            Route::post('/store', [UserController::class, 'store']);
        });

        Route::middleware('permission:delete_users')->group(function () {
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });

    });

});