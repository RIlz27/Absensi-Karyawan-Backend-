<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    AbsensiController, AuthController, QrCodeController,
    KantorController, UserController, LaporanController, ShiftController,
    CutiController, IzinController, SetupController, AssessmentCategoryController, AssessmentController, AssessmentQuestionController
};
use App\Http\Controllers\api\UserShiftController;
use App\Models\{Shift, Kantor};

// 1. PUBLIC ROUTES
Route::post('/login', [AuthController::class , 'login']);
Route::post('/ping', fn() => response()->json(['pong' => true]));
Route::get('/initial-setup/check', [SetupController::class , 'check']);
Route::post('/initial-setup', [SetupController::class , 'store']);
Route::post('/initial-setup/reset', [SetupController::class , 'reset']);

// Endpoint khusus untuk serve avatar image melewati ngrok (menggunakan middleware API untuk CORS)
Route::get('/serve-image/{path}', function ($path) {
    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        $file = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        
        return response()->file($file, [
            'Access-Control-Allow-Origin' => '*',
            'ngrok-skip-browser-warning' => '69420'
        ]);
    }
    return response()->json(['message' => 'Image not found'], 404);
})->where('path', '.*');

// 2. PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {

    // --- MANAJEMEN SHIFT USER ---
    Route::post('/user-shifts/tambahan', [UserShiftController::class , 'storeShiftTambahan']);
    Route::post('/user-shifts/biasa', [UserShiftController::class , 'storeShiftBiasa']);
    Route::get('/user-shifts/{id}', [UserShiftController::class , 'show']);
    Route::delete('/user-shifts/{id}', [UserShiftController::class , 'destroy']);

    // --- AUTH & PROFILE ---
    Route::get('/me', fn(Request $request) => $request->user()->load('shifts'));
    Route::post('/logout', [AuthController::class , 'logout']);
    Route::post('/profile', [\App\Http\Controllers\ProfileController::class , 'updateProfile']);
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class , 'updateAvatar']);

    // --- MANAJEMEN USER ---
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{id}/role', [UserController::class , 'updateRole']);

    //--PENILAIAN
    Route::apiResource('assessment-categories', AssessmentCategoryController::class);
    Route::get('assessments/subordinates', [AssessmentController::class, 'getSubordinates']);
    Route::apiResource('assessments', AssessmentController::class)->only(['index', 'store', 'show']);
    Route::get('/assessment-questions/category/{categoryId}', [AssessmentQuestionController::class, 'getByCategory']);
Route::post('/assessment-questions', [AssessmentQuestionController::class, 'store']);
Route::delete('/assessment-questions/{id}', [AssessmentQuestionController::class, 'destroy']);

    // --- FITUR ABSENSI ---
    Route::post('/scan', [AbsensiController::class , 'scan']);
    Route::post('/scan-selfie', [AbsensiController::class , 'scanSelfie']);
    Route::get('/history', [AbsensiController::class , 'history']);
    Route::post('/generate-qr', [QrCodeController::class , 'generate']);
    Route::get('/laporan-absensi', [LaporanController::class , 'index']);
    Route::get('/laporan-absensi/masih-di-kantor', [LaporanController::class , 'masihDiKantor']);
    Route::get('/admin/calendar', [AbsensiController::class , 'getCalendarData']);
    Route::post('/admin/bypass', [AbsensiController::class , 'emergencyBypass']);

    // --- MANAJEMEN SHIFT MASTER ---
    Route::get('/fetch-shifts', fn() => Shift::all());
    Route::get('/shifts/{id}', [ShiftController::class , 'show']);
    Route::match (['post', 'put', 'patch'], '/shifts/{id}', [ShiftController::class , 'update']);
    Route::apiResource('shifts', ShiftController::class);

    // --- MANAJEMEN KANTOR ---
    // Di sini lu mendaftarkan endpoint 'kantors' agar sesuai dengan request frontend lu
    Route::get('/kantors', fn() => Kantor::all());
    Route::apiResource('kantor', KantorController::class)->except(['create', 'edit']);

    // --- FITUR IZIN & CUTI ---
    Route::apiResource('cuti', CutiController::class)->only(['index', 'store']);
    Route::patch('/cuti/{id}/status', [CutiController::class , 'updateStatus']);

    Route::patch('/cuti/{id}/status', [CutiController::class , 'updateStatus']);

    Route::apiResource('izin', IzinController::class)->only(['index', 'store']);
    Route::patch('/izin/{id}/status', [IzinController::class , 'updateStatus']);

    // --- FITUR KOREKSI ABSENSI ---
    Route::get('/koreksi-absensi', [\App\Http\Controllers\KoreksiAbsensiController::class , 'index']);
    Route::post('/koreksi-absensi', [\App\Http\Controllers\KoreksiAbsensiController::class , 'store']);
    Route::post('/koreksi-absensi/{id}/approve', [\App\Http\Controllers\KoreksiAbsensiController::class , 'approve']);
    Route::post('/koreksi-absensi/{id}/reject', [\App\Http\Controllers\KoreksiAbsensiController::class , 'reject']);

    // --- ZERA BULLETIN BOARD (PENGUMUMAN) ---
    Route::get('/pengumuman', [\App\Http\Controllers\PengumumanController::class , 'index']);
    Route::get('/admin/pengumuman', [\App\Http\Controllers\PengumumanController::class , 'adminIndex']);
    Route::post('/admin/pengumuman', [\App\Http\Controllers\PengumumanController::class , 'store']);
    Route::put('/admin/pengumuman/{id}', [\App\Http\Controllers\PengumumanController::class , 'update']);
    Route::delete('/admin/pengumuman/{id}', [\App\Http\Controllers\PengumumanController::class , 'destroy']);

    // --- REPORTING SYSTEM ---
    Route::get('/laporan/statistik', [\App\Http\Controllers\LaporanController::class , 'getStatistik']);
    Route::get('/laporan/peringkat', [\App\Http\Controllers\LaporanController::class , 'getPeringkat']);
    Route::get('/laporan/harian', [\App\Http\Controllers\LaporanController::class , 'getLaporanHarian']);
    Route::get('/laporan/bulanan', [\App\Http\Controllers\LaporanController::class , 'getLaporanBulanan']);
    Route::get('/laporan/export/{type}', [\App\Http\Controllers\LaporanController::class , 'exportLaporan']);
    
    // --- GAMIFICATION (POIN & TOKEN) ---
    Route::get('/gamification/points', [\App\Http\Controllers\Api\GamificationController::class, 'getPointStatus']);
    Route::get('/gamification/store', [\App\Http\Controllers\Api\GamificationController::class, 'getStoreItems']);
    Route::post('/gamification/buy/{itemId}', [\App\Http\Controllers\Api\GamificationController::class, 'buyItem']);
    Route::get('/gamification/my-tokens', [\App\Http\Controllers\Api\GamificationController::class, 'getMyTokens']);

    // --- GAMIFICATION (ADMIN SIDE) ---
    Route::prefix('admin/gamification')->group(function () {
        // Manajemen Rules
        Route::get('/rules', [\App\Http\Controllers\Api\AdminGamificationController::class, 'getRules']);
        Route::post('/rules', [\App\Http\Controllers\Api\AdminGamificationController::class, 'storeRule']);
        Route::put('/rules/{id}', [\App\Http\Controllers\Api\AdminGamificationController::class, 'updateRule']);
        Route::delete('/rules/{id}', [\App\Http\Controllers\Api\AdminGamificationController::class, 'destroyRule']);

        // Manajemen Items (Toko)
        Route::get('/items', [\App\Http\Controllers\Api\AdminGamificationController::class, 'getItems']);
        Route::post('/items', [\App\Http\Controllers\Api\AdminGamificationController::class, 'storeItem']);
        Route::put('/items/{id}', [\App\Http\Controllers\Api\AdminGamificationController::class, 'updateItem']);
        Route::delete('/items/{id}', [\App\Http\Controllers\Api\AdminGamificationController::class, 'destroyItem']);

        // Audit Trail Poin
        Route::get('/ledgers', [\App\Http\Controllers\Api\AdminGamificationController::class, 'getLedgers']);

        // Leaderboard (Analitik Integritas)
        Route::get('/leaderboard', [\App\Http\Controllers\Api\AdminGamificationController::class, 'getLeaderboard']);

        // Manual Poin Kasir (Penyesuaian Manual Karyawan)
        Route::post('/manual-points', [\App\Http\Controllers\Api\AdminGamificationController::class, 'manualPointAdjustment']);

        // Validasi Voucher Karyawan
        Route::get('/tokens', [\App\Http\Controllers\Api\AdminGamificationController::class, 'getTokens']);
        Route::post('/tokens/{id}/use', [\App\Http\Controllers\Api\AdminGamificationController::class, 'markTokenUsed']);
    });
});

