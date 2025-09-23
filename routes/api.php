<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

Route::controller(Controllers\ScreeningController::class)->group(function () {
    Route::get('/screenings', 'index');
    Route::get('/screenings/result', 'withResult');
});

Route::prefix('check-up')->controller(Controllers\CheckUpResultController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
});
