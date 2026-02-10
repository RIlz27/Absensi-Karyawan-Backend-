<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QrCode;
use Carbon\Carbon;
use Illuminate\Support\Str;

class QrController extends Controller
{
    public function generate(Request $request)
    {
        // Validasi dulu, kalau gagal dia bakal balikin error 422 ke React lo
        $request->validate([
            'type' => 'required|in:masuk,pulang',
            'kantor_id' => 'required'
        ]);

        $token = Str::random(40);

        $qr = QrCode::create([
            'kode' => $token,
            'kantor_id' => $request->kantor_id,
            'type' => $request->type,
            'is_active' => true,
            'expired_at' => Carbon::now()->addSecond(30),
        ]);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'data' => $qr
        ]);
    }
}
