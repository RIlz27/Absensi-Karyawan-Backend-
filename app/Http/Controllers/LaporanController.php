<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Kantor;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    /**
     * Metode Asli: Laporan Absensi List (Frontend Admin - Laporan.jsx)
     */
    public function index(Request $request)
    {
        $query = Absensi::with(['user', 'shift', 'kantor']);

        // Filter Tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        // Filter Kantor
        if ($request->kantor_id) {
            $query->where('kantor_id', $request->kantor_id);
        }

        $reports = $query->orderBy('tanggal', 'desc')->get();

        return response()->json($reports);
    }

    /**
     * Metode Asli: Masih di Kantor
     */
    public function masihDiKantor(Request $request)
    {
        $query = Absensi::with(['user', 'shift', 'kantor'])
            // Cari absensi dalam radius 24 jam terakhir yang pulang-nya masih kosong
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang');

        if ($request->kantor_id) {
            $query->where('kantor_id', $request->kantor_id);
        }

        $reports = $query->orderBy('jam_masuk', 'desc')->get();

        return response()->json($reports);
    }

    private function getWorkDays($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1);
        // Jika bulan ini, hitung sampai hari ini. Jika bulan lalu, hitung sampai akhir bulan.
        $endDate = ($month == now()->month && $year == now()->year)
            ? now()
            : $startDate->copy()->endOfMonth();

        $workDays = 0;
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (!$date->isWeekend()) {
                $workDays++;
            }
        }
        return max($workDays, 1); // Minimal 1 hari biar gak division by zero
    }

    public function getStatistik(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $workDays = $this->getWorkDays($month, $year);
        $totalUsers = User::count() ?: 1;
        $totalHariKerjaEfektif = $workDays * $totalUsers;

        // 1. Total Hadir
        $totalHadir = Absensi::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereNotNull('jam_masuk')
            ->count();

        // 2. Total Terlambat (Ganti JOIN jadi whereHas biar aman)
        $totalTerlambat = Absensi::with('shift')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->whereNotNull('jam_masuk')
            ->whereHas('shift') // Pastikan relasi shift ada
            ->get()
            ->filter(function ($absensi) {
                // Bandingkan jam_masuk absensi dengan jam_masuk di tabel shift
                return $absensi->jam_masuk > $absensi->shift->jam_masuk;
            })
            ->count();

        // 3. Total Izin
        $totalIzin = Izin::where('status', 'Approved')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->count();

        // 4. Hitung Persentase
        $kehadiranPercent = round(($totalHadir / $totalHariKerjaEfektif) * 100, 1);
        $terlambatPercent = round(($totalTerlambat / $totalHariKerjaEfektif) * 100, 1);
        $izinPercent = round(($totalIzin / $totalHariKerjaEfektif) * 100, 1);

        $totalAlfa = max($totalHariKerjaEfektif - $totalHadir - $totalIzin, 0);
        $alfaPercent = round(($totalAlfa / $totalHariKerjaEfektif) * 100, 1);

        return response()->json([
            'kehadiran' => min($kehadiranPercent, 100),
            'terlambat' => $terlambatPercent,
            'alfa' => $alfaPercent,
            'izin_cuti' => $izinPercent,
            'detail' => [
                'total_hadir' => $totalHadir,
                'total_terlambat' => $totalTerlambat,
                'total_alfa' => $totalAlfa,
                'total_izin' => $totalIzin,
                'hari_kerja_efektif' => $totalHariKerjaEfektif
            ]
        ]);
    }

    public function getPeringkat(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;
        $workDays = $this->getWorkDays($month, $year);

        // Eager load absensi & shift buat efisiensi
        $users = User::all();
        $rankings = [];

        foreach ($users as $user) {
            $absensis = Absensi::with('shift')
                ->where('user_id', $user->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->get();

            $hadir = $absensis->whereNotNull('jam_masuk')->count();

            $terlambat = $absensis->filter(function ($a) {
                return $a->shift && $a->jam_masuk > $a->shift->jam_masuk;
            })->count();

            $izin = Izin::where('user_id', $user->id)
                ->where('status', 'Approved')
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->count();

            $alfa = max($workDays - $hadir - $izin, 0);
            $score = ($hadir * 10) - ($terlambat * 5) - ($alfa * 20);

            $rankings[] = [
                'user' => $user,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'alfa' => $alfa,
                'izin' => $izin,
                'score' => $score
            ];
        }

        usort($rankings, fn($a, $b) => $b['score'] <=> $a['score']);
        return response()->json(array_slice($rankings, 0, 10));
    }

    /**
     * 3. Logic Laporan Harian (Audit Detail)
     */
    public function getLaporanHarian(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->format('Y-m-d');
        $users = User::with(['absensis' => function ($q) use ($tanggal) {
            $q->whereDate('tanggal', $tanggal)->with(['shift', 'kantor']);
        }])->get();

        $laporan = [];
        $no = 1;

        foreach ($users as $user) {
            $absensi = $user->absensis->first();
            $izin = Izin::where('user_id', $user->id)->whereDate('tanggal', $tanggal)->where('status', 'Approved')->first();

            // AMBIL HARI (Senin, Selasa, dst) dari variabel $tanggal
            $namaHari = \Carbon\Carbon::parse($tanggal)->translatedFormat('l');

            // QUERY SHIFT pake kolom 'hari' (sesuai withPivot di Model User)
            $userShift = \Illuminate\Support\Facades\DB::table('user_shifts')
                ->where('user_id', $user->id)
                ->where('hari', $namaHari) // Ganti 'tanggal' jadi 'hari'
                ->first();

            $status = 'Alfa';
            $jam_masuk = '-';
            $jam_pulang = '-';
            $lokasi = '-';

            if ($absensi) {
                $jam_masuk = $absensi->jam_masuk ?? '-';
                $jam_pulang = $absensi->jam_pulang ?? '-';
                $lokasi = $absensi->kantor->nama ?? '-';

                if ($absensi->shift && $absensi->jam_masuk > $absensi->shift->jam_masuk) {
                    $status = 'Terlambat';
                } else {
                    $status = 'Hadir';
                }
            } elseif ($izin) {
                $status = 'Izin/Cuti';
            }

            $laporan[] = [
                'no' => $no++,
                'nip' => $user->nip,
                'nama' => $user->name,
                'role' => $user->role,
                // Cek tipe_shift, kalo kolom tipe_shift juga gak ada, kita pake logika 'Tambahan' kalo recordnya ada
                'shift' => $userShift ? ($userShift->tipe_shift ?? 'Tambahan') : 'Biasa',
                'jam_masuk' => $jam_masuk,
                'jam_pulang' => $jam_pulang,
                'lokasi' => $lokasi,
                'status' => $status,
            ];
        }

        return response()->json($laporan);
    }

    /**
     * 4. Logic Laporan Bulanan (Performa & Akumulasi)
     */
    public function getLaporanBulanan(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $workDays = $this->getWorkDays($month, $year);
        $users = User::all();
        $laporan = [];
        $no = 1;

        foreach ($users as $user) {
            // Plan 9 Sync: Pulang Cepat + Izin Approved di hari yg sama = Hadir
            // Untuk simplifikasi perhitungan 'Hadir', semua record absensi berjam_masuk adalah hadir (terlepas dia pulang cepat/terlambat)
            $hadir = Absensi::where('user_id', $user->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->whereNotNull('jam_masuk')
                ->count();

            $terlambat = Absensi::join('shifts', 'absensis.shift_id', '=', 'shifts.id')
                ->where('absensis.user_id', $user->id)
                ->whereMonth('absensis.tanggal', $month)
                ->whereYear('absensis.tanggal', $year)
                ->whereColumn('absensis.jam_masuk', '>', 'shifts.jam_masuk')
                ->count();

            $izin = Izin::where('user_id', $user->id)
                ->where('status', 'Approved')
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->count();

            // Total Alfa
            $alfa = max($workDays - $hadir - $izin, 0);

            // Jika ada tabel/logic lembur, bisa diextract disini, dummy 0 untuk saat ini atau query ke Lembur table:
            $lembur = DB::table('lemburs')->where('user_id', $user->id)->whereMonth('tanggal', $month)->count() ?? 0;

            $score = ($hadir * 10) - ($terlambat * 5) - ($alfa * 20);


            $laporan[] = [
                'no' => $no++,
                'nip' => $user->nip,
                'nama' => $user->name,
                'total_hadir' => $hadir,
                'total_terlambat' => $terlambat,
                'total_izin' => $izin,
                'total_alfa' => $alfa,
                'total_lembur' => $lembur,
                'total_poin' => $score
            ];
        }

        return response()->json($laporan);
    }

    /**
     * 5. Fitur Ekspor Formal (Excel/PDF)
     */
    public function exportLaporan(Request $request, $type)
    {
        $format = $request->format ?? 'bulanan'; // harian atau bulanan
        $kantor = Kantor::first()->nama ?? 'Kantor Absensi';
        $tanggalPilih = $request->tanggal ?? now()->format('d M Y');
        $bulanPilih = $request->month ?? now()->month;
        $tahunPilih = $request->year ?? now()->year;
        $namaBulan = Carbon::create($tahunPilih, $bulanPilih, 1)->translatedFormat('F');

        $adminName = Auth::user() ? Auth::user()->name : 'System Admin';
        $timestamp = now()->translatedFormat('d F Y H:i:s');

        $judulLaporan = $format === 'harian'
            ? "Rekap Data Karyawan Harian - $tanggalPilih"
            : "Rekap Data Karyawan Bulanan - $namaBulan $tahunPilih";

        $filename = "Laporan_" . ucfirst($format) . "_" . str_replace(' ', '_', $judulLaporan) . ".pdf";

        // Fetch Data matching logic
        if ($format === 'harian') {
            $data = $this->getLaporanHarian($request)->getData(true);
        } else {
            $data = $this->getLaporanBulanan($request)->getData(true);
        }

        // Return Data directly if UI prefers CSV parsing or if we want to build PDF here
        if ($type === 'pdf') {
            // Karena view 'exports.harian' / 'bulanan' belum ada di resources/views, kita bisa generate HTML dinamis disini!
            $html = $this->generateHtmlTable($judulLaporan, $kantor, $timestamp, $adminName, $data, $format);

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
            return $pdf->download($filename);
        }

        // Return JSON fallback
        return response()->json(['message' => 'Format excel please use frontend XLSX atau sesuaikan Export class Excel']);
    }

    private function generateHtmlTable($judul, $kantor, $timestamp, $admin, $data, $format)
    {
        $html = "
        <style>
            body { font-family: sans-serif; }
            h2, h3 { text-align: center; margin: 0; }
            .meta { margin-bottom: 20px; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            th { background-color: #f2f2f2; }
            tr:nth-child(even) { background-color: #f9f9f9; } /* Zebra Striping */
        </style>
        <h2>$judul</h2>
        <h3>$kantor</h3>
        <div class='meta'>
            <p><strong>Waktu Dicetak:</strong> $timestamp</p>
            <p><strong>Dicetak Oleh:</strong> $admin</p>
        </div>
        <table>
            <thead>";

        if ($format === 'harian') {
            $html .= "<tr><th>No</th><th>NIP</th><th>Nama</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Lokasi</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>";
            foreach ($data as $row) {
                $html .= "<tr><td>{$row['no']}</td><td>{$row['nip']}</td><td>{$row['nama']}</td><td>{$row['jam_masuk']}</td><td>{$row['jam_pulang']}</td><td>{$row['lokasi']}</td><td>{$row['status']}</td><td>{$row['keterangan']}</td></tr>";
            }
        } else {
            $html .= "<tr><th>No</th><th>NIP</th><th>Nama</th><th>Total Hadir</th><th>Total Terlambat</th><th>Total Izin/Cuti</th><th>Total Alfa</th><th>Total Lembur</th><th>Total Poin</th></tr></thead><tbody>";
            foreach ($data as $row) {
                $html .= "<tr><td>{$row['no']}</td><td>{$row['nip']}</td><td>{$row['nama']}</td><td>{$row['total_hadir']}</td><td>{$row['total_terlambat']}</td><td>{$row['total_izin']}</td><td>{$row['total_alfa']}</td><td>{$row['total_lembur']}</td><td>{$row['total_poin']}</td></tr>";
            }
        }

        $html .= "</tbody></table>";
        return $html;
    }
}
