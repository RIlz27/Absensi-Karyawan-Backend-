<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;


Route::post('/absensi/scan', [AbsensiController::class, 'scan']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

Route::post('/ping', function () {
    return response()->json(['pong' => true]);
});
