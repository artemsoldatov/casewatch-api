<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/{id}', [AlertController::class, 'show']);
    Route::get('/alerts/{id}/assessment', [AlertController::class, 'assessment']);
    Route::get('/alerts/{id}/transactions', [AlertController::class, 'transactions']);
    Route::get('/alerts/{id}/audit', [AlertController::class, 'audit']);
});
