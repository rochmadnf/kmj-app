<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

Route::controller(Controllers\ScreeningController::class)->group(function () {
    Route::get('/screenings', 'index');
});

Route::controller(Controllers\CheckUpResultController::class)->group(function () {
    Route::post('/check-up', 'store');
});