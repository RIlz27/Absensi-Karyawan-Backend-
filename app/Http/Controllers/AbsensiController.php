<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\Kantor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    use \App\Traits\AttendanceSync;

    public function scan(Request $request)
    {
        // Validasi Input
        $request->validate([
            'kode_qr' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $now = Carbon::now();
        $hariInggris = $now->format('l');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
    
        // CEK QR VALID
        $qr = QrCode::where('kode', $request->kode_qr)
            ->where('is_active', true)
            ->where('expired_at', '>', $now)
            ->first();

        if (!$qr) {
            return response()->json(['message' => 'QR Code sudah expired tidak valid'], 403);
        }

        // AMBIL DATA KANTOR 
        $kantor = Kantor::find($qr->kantor_id);
        if (!$kantor) {
            return response()->json(['message' => 'Data kantor tidak ditemukan'], 404);
        }

        // CEK SHIFT USER HARI INI
        $shiftUserQuery = $user->shifts()
            ->wherePivot('hari', $hariInggris)
            ->wherePivot('kantor_id', $qr->kantor_id)
            ->get();
            
        $shiftUser = $shiftUserQuery->firstWhere('pivot.tipe', 'tambahan') 
            ?? $shiftUserQuery->firstWhere('pivot.tipe', 'biasa') 
            ?? $shiftUserQuery->first();

        if (!$shiftUser) {
            Log::error("Shift tidak ditemukan - User: {$user->id}, Hari: {$hariInggris}, Kantor: {$qr->kantor_id}");
            return response()->json([
                'message' => "Jadwal shift hari $hariInggris tidak ditemukan di kantor ini",
                'debug' => [
                    'hari' => $hariInggris,
                    'kantor_id' => $qr->kantor_id
                ]
            ], 403);
        }

        // CEK JARAK GPS 
        $distance = $this->haversine(
            $request->latitude,
            $request->longitude,
            $kantor->latitude,
            $kantor->longitude
        );

        if ($distance > $kantor->radius_meter) {
            return response()->json([
                'message' => 'Anda berada di luar radius kantor!',
                'jarak' => round($distance) . ' meter',
                'radius_maksimal' => $kantor->radius_meter . ' meter'
            ], 403);
        }

        // 4. Logic Masuk atau Pulang (Check Last 24h)
        $absensiTerakhir = Absensi::where('user_id', $user->id)
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->orderBy('id', 'desc')
            ->first();

        // --- LOGIC ABSEN MASUK ---
        if (!$absensiTerakhir || $absensiTerakhir->jam_pulang !== null) {

            // Cek jika sudah pernah absen lengkap hari ini
            $sudahSelesai = Absensi::where('user_id', $user->id)
                ->whereDate('tanggal', $now->toDateString())
                ->whereNotNull('jam_pulang')
                ->exists();

            if ($sudahSelesai) return response()->json(['message' => 'Sudah absen masuk & pulang hari ini'], 400);

            // Hitung status keterlambatan
            $jamMasukShift = Carbon::createFromFormat('H:i:s', $shiftUser->jam_masuk);
            $batasToleransi = $jamMasukShift->copy()->addMinutes($kantor->toleransi_menit ?? 15);
            $status = $now->toTimeString() > $batasToleransi->toTimeString() ? 'Terlambat' : 'Hadir';

            return DB::transaction(function () use ($user, $shiftUser, $kantor, $now, $status, $request) {
                $data = Absensi::create([
                    'user_id'   => $user->id,
                    'shift_id'  => $shiftUser->id,
                    'kantor_id' => $kantor->id,
                    'tanggal'   => $now->toDateString(),
                    'jam_masuk' => $now,
                    'status'    => $status,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                    'metode'    => 'QR'
                ]);

                // PROSES POIN
                $pointsAwarded = $this->processAbsensiPoints($data, $user);
                $totalPoints = collect($pointsAwarded)->sum('amount');

                return response()->json([
                    'message' => "Absen masuk berhasil ($status)", 
                    'data' => $data,
                    'points_earned' => $pointsAwarded,
                    'total_points_earned' => $totalPoints
                ]);
            });
        }
        
        // --- LOGIC ABSEN PULANG ---
        if ($absensiTerakhir->jam_pulang === null) {
            $absensiTerakhir->update(['jam_pulang' => $now]);

            // Sync dengan data lembur jika ada
            $activeLembur = \App\Models\Lembur::where('user_id', $user->id)
                ->whereDate('tanggal', Carbon::parse($absensiTerakhir->tanggal))
                ->where('status', 'Approved')
                ->first();

            if ($activeLembur && $now->toTimeString() < $activeLembur->jam_selesai) {
                $activeLembur->update(['jam_selesai' => $now->toTimeString()]);
            }

            return response()->json(['message' => 'Absen pulang berhasil', 'data' => $absensiTerakhir]);
        }
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        
        // SYNC ALFA REAL-TIME (Agar poin langsung berkurang pas dashboard dibuka)
        $this->syncAlfaStatus($user);

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

    public function getCalendarData(Request $request)
    {
        // Admin & Manager Only
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }

        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $absensis = Absensi::with('user:id,name')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get();

        $events = [];

        foreach ($absensis as $absen) {
            $color = '#3b82f6'; // default blue (izin/cuti fallback)
            $className = 'bg-blue-500 text-white';

            if ($absen->status === 'Hadir') {
                $color = '#22c55e'; 
                $className = 'bg-success-500 text-white';
            } elseif ($absen->status === 'Terlambat') {
                $color = '#eab308'; 
                $className = 'bg-warning-500 text-white';
            } elseif ($absen->status === 'Alfa') {
                $color = '#ef4444'; // red
                $className = 'bg-danger-500 text-white';
            }

            $events[] = [
                'id' => $absen->id,
                'title' => $absen->user->name . ' (' . $absen->status . ')',
                'start' => $absen->jam_masuk ?? $absen->tanggal . 'T00:00:00', 
                'end' => $absen->jam_pulang,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'className' => $className,
                'extendedProps' => [
                    'status' => $absen->status,
                    'user_id' => $absen->user_id,
                    'metode' => $absen->metode,
                ]
            ];
        }

        return response()->json($events);
    }

    /**
     * Pillar 4: The Bypass
     * Manual injection of attendance records by Manager/Admin
     */
    public function emergencyBypass(Request $request)
    {
        $admin = Auth::user();
        if (!in_array($admin->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'shift_id' => 'required|exists:shifts,id',
            'status' => 'required|in:Hadir,Terlambat,Izin,Sakit,Alfa',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i'
        ]);

        $targetUser = \App\Models\User::findOrFail($request->user_id);
        $kantorId = $targetUser->kantor_id; // Default fallback to user's assigned branch

        // Check if record already exists for this date to prevent duplicates
        $existing = Absensi::where('user_id', $targetUser->id)
            ->whereDate('tanggal', $request->tanggal)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Absensi untuk user ini di tanggal tersebut sudah ada!'], 400);
        }

        // Insert new Bypass Record
        $jamMasukFull = $request->jam_masuk ? Carbon::parse($request->tanggal . ' ' . $request->jam_masuk) : null;
        $jamPulangFull = $request->jam_pulang ? Carbon::parse($request->tanggal . ' ' . $request->jam_pulang) : null;

        $absensiBaru = Absensi::create([
            'user_id'   => $targetUser->id,
            'shift_id'  => $request->shift_id,
            'kantor_id' => $kantorId,
            'tanggal'   => $request->tanggal,
            'jam_masuk' => $jamMasukFull,
            'jam_pulang' => $jamPulangFull,
            'status'    => $request->status,
            'latitude'  => null,
            'longitude' => null,
            'metode'    => 'Bypass (Manual)',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Emergency Bypass berhasil ditambahkan!',
            'data' => $absensiBaru,
        ]);
    }

    private function processAbsensiPoints($absensi, $user)
    {
        $role = $user->role ?? 'karyawan';
        $rules = \App\Models\PointRule::whereIn('target_role', [$role, 'Semua'])
            ->orderBy('id', 'asc')
            ->get();

        if (!$absensi->jam_masuk || !$absensi->shift) return [];

        $waktuAbsen = \Carbon\Carbon::parse($absensi->jam_masuk)->format('H:i:s');
        $jadwalMasuk = $absensi->shift->jam_masuk;
        
        // Selisih menit (Positif = Telat, Negatif = Lebih Awal)
        $selisihMenit = (strtotime($waktuAbsen) - strtotime($jadwalMasuk)) / 60;

        // --- TOKEN INTERCEPTOR ---
        if ($selisihMenit > 0) {
            $availableToken = \App\Models\UserToken::where('user_id', $user->id)
                ->where('status', 'AVAILABLE')
                ->whereHas('item', function($q) use ($selisihMenit) {
                    $q->where('type', 'LATE_EXEMPTION')
                      ->where('value', '>=', $selisihMenit);
                })
                ->with('item')
                ->first();

            if ($availableToken) {
                // Gunakan Token
                $availableToken->update([
                    'status' => 'USED',
                    'used_at_attendance_id' => $absensi->id
                ]);

                // Ubah status absensi & info
                $absensi->update([
                    'status' => 'Hadir Tepat Waktu (Token Used)'
                ]);

                // Reset selisih menit ke 0 (menganggap tepat waktu) untuk perhitungan poin
                $selisihMenit = 0;
            }
        }
        // -------------------------

        $pointsAwarded = [];

        foreach ($rules as $rule) {
            if ($rule->condition_value === 'ALFA') continue;

            $isMatch = false;

            if ($rule->condition_value === 'HADIR') {
                $isMatch = true;
            } elseif (str_contains($rule->condition_value, ':')) {
                $waktuRule = $rule->condition_value;
                switch ($rule->condition_operator) {
                    case '<': $isMatch = $waktuAbsen < $waktuRule; break;
                    case '<=': $isMatch = $waktuAbsen <= $waktuRule; break;
                    case '>': $isMatch = $waktuAbsen > $waktuRule; break;
                    case '>=': $isMatch = $waktuAbsen >= $waktuRule; break;
                    case '=': $isMatch = $waktuAbsen == $waktuRule; break;
                    case 'BETWEEN':
                        $parts = explode(',', $waktuRule);
                        if (count($parts) === 2) {
                            $isMatch = $waktuAbsen >= trim($parts[0]) && $waktuAbsen <= trim($parts[1]);
                        }
                        break;
                }
            } else {
                $ruleValue = $rule->condition_value; // Keep as string for splitting
                switch ($rule->condition_operator) {
                    case '<': $isMatch = $selisihMenit < (int)$ruleValue; break;
                    case '<=': $isMatch = $selisihMenit <= (int)$ruleValue; break;
                    case '>': $isMatch = $selisihMenit > (int)$ruleValue; break;
                    case '>=': $isMatch = $selisihMenit >= (int)$ruleValue; break;
                    case '=': $isMatch = $selisihMenit == (int)$ruleValue; break;
                    case 'BETWEEN':
                        $parts = explode(',', $ruleValue);
                        if (count($parts) === 2) {
                            $isMatch = $selisihMenit >= (int)trim($parts[0]) && $selisihMenit <= (int)trim($parts[1]);
                        }
                        break;
                }
            }

            if ($isMatch) {
                // Update User Balance & Create Ledger
                $saldoBaru = $user->points + $rule->point_modifier;

                $ledger = \App\Models\PointLedger::create([
                    'user_id' => $user->id,
                    'transaction_type' => $rule->point_modifier > 0 ? 'EARN' : 'PENALTY',
                    'amount' => $rule->point_modifier,
                    'current_balance' => $saldoBaru,
                    'description' => "Penyesuaian: Memenuhi kriteria '" . $rule->rule_name . "'",
                ]);

                $user->update(['points' => $saldoBaru]);
                $pointsAwarded[] = $ledger;
                
                // Break untuk menghindari double point if criteria overlap (Earliest/First Win)
                break; 
            }
        }

        return $pointsAwarded;
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

    public function scanSelfie(Request $request)
    {
        $request->validate([
            'foto' => 'required|string', // Base64 image
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $now = Carbon::now();
        $hariInggris = $now->format('l');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Cari Shift User hari ini
        $shiftUserQuery = $user->shifts()->wherePivot('hari', $hariInggris)->get();
        $shiftUser = $shiftUserQuery->firstWhere('pivot.tipe', 'tambahan') 
            ?? $shiftUserQuery->firstWhere('pivot.tipe', 'biasa') 
            ?? $shiftUserQuery->first();
        if (!$shiftUser) {
            return response()->json(['message' => "Jadwal shift hari $hariInggris tidak ditemukan"], 403);
        }

        $kantor = Kantor::find($shiftUser->pivot->kantor_id);
        if (!$kantor) {
            $kantor = Kantor::first(); // Fallback
        }

        // Cek Jarak GPS
        $distance = $this->haversine($request->latitude, $request->longitude, $kantor->latitude, $kantor->longitude);
        if ($distance > $kantor->radius_meter) {
            return response()->json([
                'message' => 'Anda berada di luar radius kantor!',
                'jarak' => round($distance) . ' m',
            ], 403);
        }

        // Proses Foto Base64
        $image_parts = explode(";base64,", $request->foto);
        if (count($image_parts) != 2) {
            return response()->json(['message' => 'Format foto tidak valid'], 400);
        }
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1] ?? 'jpeg';
        $image_base64 = base64_decode($image_parts[1]);

        $fileName = 'selfie_' . $user->id . '_' . time() . '.' . $image_type;
        // Simpan langsung (tanpa upload gallery / hapus exif inheren)
        \Illuminate\Support\Facades\Storage::disk('public')->put('absensi_selfie/' . $fileName, $image_base64);

        // LOGIC LINTAS HARI (Sama dengan QR Scanner)
        $absensiTerakhir = Absensi::where('user_id', $user->id)
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->orderBy('id', 'desc')
            ->first();

        if (!$absensiTerakhir || $absensiTerakhir->jam_pulang !== null) {
            // PROTEKSI DOUBLE-IN
            $sudahSelesaiHariIni = Absensi::where('user_id', $user->id)
                ->whereDate('tanggal', $now->toDateString())
                ->whereNotNull('jam_pulang')
                ->exists();

            if ($sudahSelesaiHariIni) {
                return response()->json(['message' => 'Anda sudah absen masuk & pulang hari ini.'], 400);
            }

            // --- LOGIC ABSEN MASUK ---
            $jamMasukShift = Carbon::createFromFormat('H:i:s', $shiftUser->jam_masuk);
            $batasToleransi = (clone $jamMasukShift)->addMinutes($kantor->toleransi_menit ?? 15);
            $status = $now->toTimeString() > $batasToleransi->toTimeString() ? 'Terlambat' : 'Hadir';

            return DB::transaction(function () use ($user, $shiftUser, $kantor, $now, $status, $request) {
                $absensiBaru = Absensi::create([
                    'user_id'   => $user->id,
                    'shift_id'  => $shiftUser->id,
                    'kantor_id' => $kantor->id,
                    'tanggal'   => $now->toDateString(),
                    'jam_masuk' => $now,
                    'status'    => $status,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                    'metode'    => 'Manual', 
                ]);

                // PROSES POIN
                $pointsAwarded = $this->processAbsensiPoints($absensiBaru, $user);
                $totalPoints = collect($pointsAwarded)->sum('amount');

                return response()->json([
                    'message' => 'Absen Selfie (Masuk) berhasil! Status: ' . $status,
                    'data' => $absensiBaru,
                    'points_earned' => $pointsAwarded,
                    'total_points_earned' => $totalPoints
                ]);
            });
        }

        // --- LOGIC ABSEN PULANG ---
        if ($absensiTerakhir->jam_pulang === null) {
            $absensiTerakhir->update([
                'jam_pulang' => $now
            ]);

            // FINISH EARLY LEMBUR
            $activeLembur = \App\Models\Lembur::where('user_id', $user->id)
                ->whereDate('tanggal', clone \Carbon\Carbon::parse($absensiTerakhir->tanggal))
                ->where('status', 'Approved')
                ->first();

            if ($activeLembur && $now->toTimeString() < $activeLembur->jam_selesai) {
                $activeLembur->update(['jam_selesai' => $now->toTimeString()]);
            }

            return response()->json([
                'message' => 'Absen pulang berhasil. Hati-hati di jalan!',
                'data' => $absensiTerakhir
            ]);
        }
    }
}
