<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    AbsensiController, AuthController, QrCodeController, 
    KantorController, UserController, LaporanController, ShiftController,
    CutiController, IzinController, SetupController
};
use App\Http\Controllers\api\UserShiftController;
use App\Models\{Shift, Kantor};

// 1. PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/ping', fn() => response()->json(['pong' => true]));
Route::get('/initial-setup/check', [SetupController::class, 'check']);
Route::post('/initial-setup', [SetupController::class, 'store']);
Route::post('/initial-setup/reset', [SetupController::class, 'reset']);

// 2. PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {
    
    // --- MANAJEMEN SHIFT USER ---
    Route::post('/user-shifts/tambahan', [UserShiftController::class, 'storeShiftTambahan']);
    Route::post('/user-shifts/biasa', [UserShiftController::class, 'storeShiftBiasa']);
    Route::get('/user-shifts/{id}', [UserShiftController::class, 'show']); 
    Route::delete('/user-shifts/{id}', [UserShiftController::class, 'destroy']);

    // --- AUTH & PROFILE ---
    Route::get('/me', fn(Request $request) => $request->user()->load('shifts'));
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile', [\App\Http\Controllers\ProfileController::class, 'updateProfile']);
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class, 'updateAvatar']);

    // --- MANAJEMEN USER ---
    Route::apiResource('users', UserController::class);

    // --- FITUR ABSENSI ---
    Route::post('/scan', [AbsensiController::class, 'scan']);
    Route::post('/scan-selfie', [AbsensiController::class, 'scanSelfie']);
    Route::get('/history', [AbsensiController::class, 'history']);
    Route::post('/generate-qr', [QrCodeController::class, 'generate']);
    Route::get('/laporan-absensi', [LaporanController::class, 'index']);
    Route::get('/laporan-absensi/masih-di-kantor', [LaporanController::class, 'masihDiKantor']);
    
    // --- MANAJEMEN SHIFT MASTER ---
    Route::get('/fetch-shifts', fn() => Shift::all());
    Route::get('/shifts/{id}', [ShiftController::class, 'show']);
    Route::match(['post', 'put', 'patch'], '/shifts/{id}', [ShiftController::class, 'update']);
    Route::apiResource('shifts', ShiftController::class);

    // --- MANAJEMEN KANTOR ---
    // Di sini lu mendaftarkan endpoint 'kantors' agar sesuai dengan request frontend lu
    Route::get('/kantors', fn() => Kantor::all()); 
    Route::apiResource('kantor', KantorController::class)->except(['create', 'edit']);

    // --- FITUR IZIN & CUTI ---
    Route::apiResource('cuti', CutiController::class)->only(['index', 'store']);
    Route::patch('/cuti/{id}/status', [CutiController::class, 'updateStatus']);

    Route::patch('/cuti/{id}/status', [CutiController::class, 'updateStatus']);

    Route::apiResource('izin', IzinController::class)->only(['index', 'store']);
    Route::patch('/izin/{id}/status', [IzinController::class, 'updateStatus']);

    // --- FITUR KOREKSI ABSENSI ---
    Route::get('/koreksi-absensi', [\App\Http\Controllers\KoreksiAbsensiController::class, 'index']);
    Route::post('/koreksi-absensi', [\App\Http\Controllers\KoreksiAbsensiController::class, 'store']);
    Route::post('/koreksi-absensi/{id}/approve', [\App\Http\Controllers\KoreksiAbsensiController::class, 'approve']);
    Route::post('/koreksi-absensi/{id}/reject', [\App\Http\Controllers\KoreksiAbsensiController::class, 'reject']);
});