<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\KantorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserShiftController;
use App\Http\Controllers\LaporanController;
use App\Models\User;
use App\Models\Shift;
use App\Models\Kantor;

// 1. PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/ping', fn() => response()->json(['pong' => true]));

// 2. PROTECTED ROUTES (Wajib Login)
Route::middleware('auth:sanctum')->group(function () {

    // --- Profile & Auth ---
    Route::get('/me', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Fitur Utama Absensi ---
    Route::post('/scan', [AbsensiController::class, 'scan']);
    Route::get('/history', [AbsensiController::class, 'history']); // Jangan lupa route history
    Route::post('/generate-qr', [QrCodeController::class, 'generate']);
    Route::get('/laporan-absensi', [LaporanController::class, 'index']);

    // --- Dropdown Data (Buat Form ManageShift) ---
    Route::get('/fetch-users', fn() => User::all());
    Route::get('/fetch-shifts', fn() => Shift::all());
    Route::get('/fetch-kantors', fn() => Kantor::all());

    // --- Manajemen User ---
    Route::apiResource('users', UserController::class);

    // --- Manajemen Shift (Plotting) ---
    Route::post('/user-shifts', [UserShiftController::class, 'store']);
    Route::post('/assign-shift', [UserShiftController::class, 'assignShift']); // Jika ini logic berbeda
    Route::delete('/user-shifts/{id}', [UserShiftController::class, 'destroy']);

    // --- Manajemen Kantor (CRUD Lengkap) ---
    Route::get('/kantor', [KantorController::class, 'index']);
    Route::post('/kantor', [KantorController::class, 'store']);
    Route::put('/kantor/{id}', [KantorController::class, 'update']);
    Route::delete('/kantor/{id}', [KantorController::class, 'destroy']);
});
