<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    AbsensiController, AuthController, QrCodeController, 
    KantorController, UserController, LaporanController, ShiftController
};
use App\Http\Controllers\api\UserShiftController;
use App\Models\{Shift, Kantor};

// 1. PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/ping', fn() => response()->json(['pong' => true]));

// 2. PROTECTED ROUTES (Wajib Login)
Route::middleware('auth:sanctum')->group(function () {
    // --- Route Statis (Taruh di ATAS) ---
    Route::post('/user-shifts/tambahan', [UserShiftController::class, 'storeShiftTambahan']);
    Route::post('/user-shifts/biasa', [UserShiftController::class, 'storeShiftBiasa']);

    // --- Route Dinamis (Taruh di BAWAH) ---
    // Pastikan rute ini ada dan method-nya GET
    Route::get('/user-shifts/{id}', [UserShiftController::class, 'show']); 
    Route::delete('/user-shifts/{id}', [UserShiftController::class, 'destroy']);

    // --- Profile & Auth ---
    Route::get('/me', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- User & Karyawan ---
    Route::apiResource('users', UserController::class);

    // --- Fitur Utama Absensi ---
    Route::post('/scan', [AbsensiController::class, 'scan']);
    Route::get('/history', [AbsensiController::class, 'history']);
    Route::post('/generate-qr', [QrCodeController::class, 'generate']);
    Route::get('/laporan-absensi', [LaporanController::class, 'index']);
    
    // Manajemen Shift Master
    Route::get('/fetch-shifts', fn() => Shift::all());
    Route::get('/shifts/{id}', [ShiftController::class, 'show']);
    Route::match(['post', 'put', 'patch'], '/shifts/{id}', [ShiftController::class, 'update']);

    // --- Manajemen Kantor ---
    Route::get('/fetch-kantors', fn() => Kantor::all());
    Route::apiResource('kantor', KantorController::class)->except(['create', 'edit']);
});