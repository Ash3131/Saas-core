<?php
use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
});

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);