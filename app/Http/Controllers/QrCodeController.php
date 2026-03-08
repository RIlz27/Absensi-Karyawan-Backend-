<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QrCodeController extends Controller
{
    public function generate(Request $request)
    {
        // 1. Validasi: Type tidak lagi strict (karena sekarang single QR)
        $request->validate([
            'kantor_id' => 'required|exists:kantors,id',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 2. Nonaktifkan QR lama spesifik untuk kantor ini (semua tipe)
                QrCode::where('kantor_id', $request->kantor_id)
                    ->update(['is_active' => false]);

                // 3. Buat Kode Unik Baru
                $newCode = Str::random(40); 

                // 4. Simpan ke database
                $qr = QrCode::create([
                    'kantor_id'  => $request->kantor_id,
                    'type'       => $request->type ?? 'general', // Fallback

                    'kode'       => $newCode,
                    'is_active'  => true,
                    'expired_at' => Carbon::now()->addSeconds(40), 
                ]);

                // 5. (Opsional) Hapus QR yang sudah expired biar database bersih
                QrCode::where('expired_at', '<', Carbon::now())->delete();

                return response()->json([
                    'success' => true,
                    'qr_string' => $newCode, // Samain key-nya dengan yang diharapkan frontend
                    'type' => $request->type,
                    'expires_in' => 40
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate QR: ' . $e->getMessage()
            ], 500);
        }
    }
}