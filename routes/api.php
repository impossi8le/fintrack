<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BalanceController;
use App\Jobs\TestRedisJob;
use App\Http\Controllers\AuthController;

// Открытые маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route::post('/test-job', function () {
//     TestRedisJob::dispatch();
//     return response()->json(['status' => 'queued']);
// });

    
// Защищённые маршруты
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/balance', [BalanceController::class, 'me']);
    Route::post('/balance/operate', [BalanceController::class, 'operate']);
    Route::get('/transactions', [BalanceController::class, 'history']);

    Route::post('/test-job', function () {
        TestRedisJob::dispatch();
        return response()->json(['status' => 'queued']);
    });
});
