<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\User;
use App\Models\Kantor;
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

        $now = Carbon::now();
        $hariIni = $now->format('l'); // Hasil: "Tuesday" (Cocok dengan Migration Inggris)

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // 2. CEK QR VALID?
        $qr = QrCode::where('kode', $request->kode_qr)
            ->where('is_active', true)
            ->where('expired_at', '>', $now)
            ->first();

        if (!$qr) {
            return response()->json(['message' => 'QR Code sudah expired atau tidak valid'], 403);
        }

        // 3. CEK SHIFT USER HARI INI
        $shiftUser = $user->shifts()
            ->wherePivot('hari', $hariIni)
            ->wherePivot('kantor_id', $qr->kantor_id)
            ->first();

        if (!$shiftUser) {
            return response()->json([
                'message' => "Jadwal shift hari $hariIni tidak ditemukan di kantor ini",
                'debug' => [
                    'hari' => $hariIni,
                    'kantor_id' => $qr->kantor_id
                ]
            ], 403);
        }

        // 4. CEK JARAK GPS (Haversine)
        $kantor = Kantor::find($qr->kantor_id);
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

        // 5. LOGIC ABSENSI (MASUK / PULANG)
        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', $now->toDateString())
            ->first();

        if (!$absensi) {
            // --- LOGIC ABSEN MASUK ---
            $jamMasukShift = Carbon::createFromFormat('H:i:s', $shiftUser->jam_masuk);
            
            // Hitung batas toleransi berdasarkan kolom toleransi_menit di tabel shifts
            $batasToleransi = (clone $jamMasukShift)->addMinutes($shiftUser->toleransi_menit ?? 0);

            // Status HARUS sesuai ENUM migration: 'Hadir' atau 'Terlambat'
            $status = $now->gt($batasToleransi) ? 'Terlambat' : 'Hadir';

            $absensi = Absensi::create([
                'user_id'   => $user->id,
                'shift_id'  => $shiftUser->id,
                'kantor_id' => $kantor->id,
                'tanggal'   => $now->toDateString(),
                'jam_masuk' => $now, // Mengirim DateTime lengkap sesuai migration
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
            // Update jam_pulang dengan DateTime sekarang
            $absensi->update([
                'jam_pulang' => $now 
            ]);

            return response()->json([
                'message' => 'Absen pulang berhasil. Hati-hati di jalan!',
                'data' => $absensi
            ]);
        }

        return response()->json(['message' => 'Anda sudah melakukan absen masuk dan pulang hari ini.'], 400);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $data = Absensi::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->with(['shift', 'kantor'])
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json($data);
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earth = 6371000; // Meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        return $earth * (2 * asin(sqrt($a)));
    }
}