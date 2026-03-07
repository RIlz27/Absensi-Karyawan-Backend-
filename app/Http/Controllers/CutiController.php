<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use Illuminate\Http\Request;

class CutiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admin sees all cuti requests
            $cutis = Cuti::with(['user', 'approver'])->latest()->get();
        } else {
            // User sees their own cuti requests
            $cutis = Cuti::where('user_id', $user->id)->latest()->get();
        }

        return response()->json($cutis);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis' => 'required|in:Tahunan,Sakit,Khusus',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:500',
        ]);

        $cuti = Cuti::create([
            'user_id' => $request->user()->id,
            'jenis' => $validated['jenis'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'alasan' => $validated['alasan'],
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dibuat',
            'data' => $cuti
        ], 201);
    }

    /**
     * Update status (Approve/Reject) by Admin.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
        ]);

        $cuti = Cuti::findOrFail($id);
        
        $cuti->update([
            'status' => $validated['status'],
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Status cuti berhasil diupdate',
            'data' => $cuti
        ]);
    }
}
