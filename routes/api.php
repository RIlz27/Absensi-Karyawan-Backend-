<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QrCodeController; // Pastikan nama controller sama dengan file-nya
use App\Http\Controllers\KantorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserShiftController;

// 1. PUBLIC ROUTES (Gak perlu login)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/ping', fn() => response()->json(['pong' => true]));

// 2. PROTECTED ROUTES (Wajib Login/Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Me
    Route::get('/me', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // Absensi & QR
    Route::post('/scan', [AbsensiController::class, 'scan']);
    Route::post('/generate-qr', [QrCodeController::class, 'generate']);
    Route::post('/generate-qr', [KantorController::class, 'generateQr']);

    // Manajemen (Admin Only - Lo bisa filter lagi nanti pake role)
    Route::get('/kantor', [KantorController::class, 'index']);
    Route::apiResource('users', UserController::class);

    // Assign Shift (Pivot)
    Route::post('/assign-shift', [UserShiftController::class, 'assignShift']);

    //Mapping Absensi
    Route::get('/kantor', [KantorController::class, 'index']);  // Ambil data
    Route::post('/kantor', [KantorController::class, 'store']); // Simpan data
    Route::put('/kantor/{id}', [KantorController::class, 'update']); // Untuk Edit
    Route::delete('/kantor/{id}', [KantorController::class, 'destroy']); // Untuk Hapus
});
