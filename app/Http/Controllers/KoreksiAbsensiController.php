<?php

namespace App\Http\Controllers;

use App\Models\KoreksiAbsensi;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KoreksiAbsensiController extends Controller
{
    // List koreksi (Utk Admin semua, utk Karyawan hanya miliknya)
    public function index()
    {
        $user = Auth::user();
        $query = KoreksiAbsensi::with(['user', 'absensi', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($user->role === 'karyawan') {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get());
    }

    // Ajukan koreksi (Oleh Karyawan)
    public function store(Request $request)
    {
        $request->validate([
            'absensi_id' => 'required|exists:absensi,id',
            'alasan' => 'required|string',
            'jam_masuk_baru' => 'nullable|date_format:H:i',
            'jam_pulang_baru' => 'nullable|date_format:H:i',
        ]);

        $user = Auth::user();

        // Pastikan absen milik dia
        $absensi = Absensi::where('id', $request->absensi_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Mencegah request ganda yang masih pending
        $existing = KoreksiAbsensi::where('absensi_id', $absensi->id)
            ->where('status', 'Pending')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Anda sudah memiliki pengajuan koreksi yang sedang diproses untuk absensi ini.'], 400);
        }

        $koreksi = KoreksiAbsensi::create([
            'user_id' => $user->id,
            'absensi_id' => $absensi->id,
            'alasan' => $request->alasan,
            'jam_masuk_baru' => $request->jam_masuk_baru,
            'jam_pulang_baru' => $request->jam_pulang_baru,
            'status' => 'Pending'
        ]);

        return response()->json([
            'message' => 'Koreksi absensi berhasil diajukan.',
            'data' => $koreksi
        ], 201);
    }

    // Approve (Oleh Admin)
    public function approve($id)
    {
        $koreksi = KoreksiAbsensi::findOrFail($id);
        if ($koreksi->status !== 'Pending') {
            return response()->json(['message' => 'Koreksi ini sudah diproses.'], 400);
        }

        $absensi = Absensi::find($koreksi->absensi_id);

        if ($koreksi->jam_masuk_baru) {
             $tanggal = Carbon::parse($absensi->tanggal)->format('Y-m-d');
             $absensi->jam_masuk = $tanggal . ' ' . $koreksi->jam_masuk_baru . ':00';
        }
        if ($koreksi->jam_pulang_baru) {
             $tanggal = Carbon::parse($absensi->tanggal)->format('Y-m-d');
             $absensi->jam_pulang = $tanggal . ' ' . $koreksi->jam_pulang_baru . ':00';
        }

        // Kalau status Alfa dan ini dikoreksi jamnya, asumsikan Hadir / Terlambat
        if ($absensi->status === 'Alfa' || $absensi->status === 'AUTO-PULANG') {
            $absensi->status = 'Hadir (Koreksi Admin)';
        }

        $absensi->save();

        $koreksi->update([
            'status' => 'Approved',
            'approved_by' => Auth::id(),
            'approved_at' => now()
        ]);

        return response()->json(['message' => 'Koreksi disetujui.', 'data' => $absensi]);
    }

    // Reject (Oleh Admin)
    public function reject($id)
    {
        $koreksi = KoreksiAbsensi::findOrFail($id);
        if ($koreksi->status !== 'Pending') {
            return response()->json(['message' => 'Koreksi ini sudah diproses.'], 400);
        }

        $koreksi->update([
            'status' => 'Rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now()
        ]);

        return response()->json(['message' => 'Koreksi ditolak.']);
    }
}
