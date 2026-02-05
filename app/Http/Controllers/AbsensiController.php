<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\UserShift;


class AbsensiController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'kode_qr' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = \App\Models\User::first();

        if (!$user) {
            return response()->json([
                'message' => 'User belum ada'
            ], 500);
        }

        $now = Carbon::now();

        // 1. QR VALID?
        $qr = QrCode::where('kode', $request->kode_qr)
            ->where('is_active', true)
            ->where('expired_at', '>', $now)
            ->first();

        if (!$qr) {
            return response()->json(['message' => 'QR tidak valid'], 403);
        }

        // 2. SHIFT USER HARI INI
        $userShift = UserShift::where('user_id', $user->id)
            ->where('kantor_id', $qr->kantor_id)
            ->whereDate('tanggal_mulai', '<=', $now)
            ->whereDate('tanggal_selesai', '>=', $now)
            ->first();

        if (!$userShift) {
            return response()->json(['message' => 'Shift tidak ditemukan'], 403);
        }

        // 3. CEK JARAK GPS
        $kantor = $userShift->kantor;

        $distance = $this->haversine(
            $request->latitude,
            $request->longitude,
            $kantor->latitude,
            $kantor->longitude
        );

        if ($distance > $kantor->radius_meter) {
            return response()->json([
                'message' => 'Diluar area kantor',
                'jarak' => round($distance)
            ], 403);
        }

        // 4. ABSENSI HARI INI
        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', $now->toDateString())
            ->first();

        if (!$absensi) {
            // ABSEN MASUK
            $absensi = Absensi::create([
                'user_id' => $user->id,
                'shift_id' => $userShift->shift_id,
                'kantor_id' => $kantor->id,
                'tanggal' => $now->toDateString(),
                'jam_masuk' => $now,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'Hadir',
                'metode' => 'QR'
            ]);

            return response()->json([
                'message' => 'Absen masuk berhasil',
                'data' => $absensi
            ]);
        }

        if (!$absensi->jam_pulang) {
            // ABSEN PULANG
            $absensi->update([
                'jam_pulang' => $now
            ]);

            return response()->json([
                'message' => 'Absen pulang berhasil',
                'data' => $absensi
            ]);
        }

        return response()->json([
            'message' => 'Sudah absen masuk & pulang'
        ], 400);
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        return $earth * (2 * asin(sqrt($a)));
    }
}
