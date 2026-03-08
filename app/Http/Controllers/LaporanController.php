<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
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

    public function masihDiKantor(Request $request)
    {
        $today = \Carbon\Carbon::today();
        
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
}