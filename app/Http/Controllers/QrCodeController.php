<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QrCodeController extends Controller
{
    public function generate(Request $request)
    {
        // 1. Validasi: QR ini buat kantor mana?
        $request->validate([
            'kantor_id' => 'required|exists:kantors,id'
        ]);

        // 2. Nonaktifkan QR lama buat kantor tersebut
        QrCode::where('kantor_id', $request->kantor_id)
            ->update(['is_active' => false]);

        // 3. Buat Kode Unik Baru
        $newCode = Str::random(32); 

        $qr = QrCode::create([
            'kantor_id' => $request->kantor_id,
            'kode' => $newCode,
            'is_active' => true,
            'expired_at' => Carbon::now()->addSeconds(40), // Kasih napas 40 detik
        ]);

        return response()->json([
            'success' => true,
            'kode' => $newCode,
            'expires_in' => 40
        ]);
    }
}