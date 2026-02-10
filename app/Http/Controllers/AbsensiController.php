<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AbsensiController extends Controller
{
    public function scan(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'kode_qr' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // 2. Definisi Waktu & User (Definisikan $now dan $hariIni DULU sebelum dipakai)
        $now = Carbon::now();
        $hariIni = $now->translatedFormat('l'); // Hasil: Senin, Selasa, dsb.

        /** @var \App\Models\User $user */
        $user = Auth::user(); // Gunakan Auth::user() yang diimport di atas

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // 3. CEK QR VALID?
        $qr = QrCode::where('kode', $request->kode_qr)
            ->where('is_active', true)
            ->where('expired_at', '>', $now)
            ->first();

        if (!$qr) {
            return response()->json(['message' => 'QR Code sudah expired atau tidak valid'], 403);
        }

        // 4. CEK SHIFT USER HARI INI
        $shiftUser = $user->shifts()
            ->wherePivot('hari', $hariIni)
            ->wherePivot('kantor_id', $qr->kantor_id)
            ->first();

        if (!$shiftUser) {
            return response()->json(['message' => "Jadwal shift hari $hariIni tidak ditemukan"], 403);
        }

        // 3. CEK JARAK GPS (Haversine)
        $kantor = \App\Models\Kantor::find($qr->kantor_id);
        $distance = $this->haversine(
            $request->latitude,
            $request->longitude,
            $kantor->latitude,
            $kantor->longitude
        );

        if ($distance > $kantor->radius_meter) {
            return response()->json([
                'message' => 'Anda berada di luar radius kantor!',
                'jarak' => round($distance) . ' meter'
            ], 403);
        }

        // 4. LOGIC ABSENSI (MASUK / PULANG)
        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', $now->toDateString())
            ->first();

        if (!$absensi) {
            // --- LOGIC ABSEN MASUK ---
            $jamMasukShift = Carbon::createFromFormat('H:i:s', $shiftUser->jam_masuk);

            // Cek telat (toleransi bisa lo tambah di sini)
            $status = $now->gt($jamMasukShift) ? 'Telat' : 'Tepat Waktu';

            $absensi = Absensi::create([
                'user_id'   => $user->id,
                'shift_id'  => $shiftUser->id,
                'kantor_id' => $kantor->id,
                'tanggal'   => $now->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status'    => $status,
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
                'metode'    => 'QR'
            ]);

            return response()->json([
                'message' => 'Absen masuk berhasil! Status: ' . $status,
                'data' => $absensi
            ]);
        }

        if (!$absensi->jam_pulang) {
            // --- LOGIC ABSEN PULANG ---
            $absensi->update([
                'jam_pulang' => $now->toTimeString()
            ]);

            return response()->json([
                'message' => 'Absen pulang berhasil. Hati-hati di jalan!',
                'data' => $absensi
            ]);
        }
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $data = Absensi::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->with(['shift', 'kantor']) // Biar keliatan absen di kantor mana & shift apa
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json($data);
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
