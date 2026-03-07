<?php

namespace App\Http\Controllers;

use App\Models\Izin;
use Illuminate\Http\Request;

class IzinController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admin sees all izin requests
            $izins = Izin::with(['user', 'approver'])->latest()->get();
        } else {
            // User sees their own izin requests
            $izins = Izin::where('user_id', $user->id)->latest()->get();
        }

        return response()->json($izins);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i:s',
            'jam_selesai' => 'required|date_format:H:i:s|after:jam_mulai',
            'alasan' => 'required|string|max:500',
        ]);

        // Note: Sometimes frontend sends H:i without seconds, so we might need H:i rule
        // However, standard date_format rules usually match exact format from DB, but we can relax it to just string validating if it's too strict. 
        // We will keep H:i:s for DB standard, but maybe allow H:i and append :00 in store logic.
        
        $izin = Izin::create([
            'user_id' => $request->user()->id,
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'alasan' => $validated['alasan'],
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil dibuat',
            'data' => $izin
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

        $izin = Izin::findOrFail($id);
        
        $izin->update([
            'status' => $validated['status'],
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Status izin berhasil diupdate',
            'data' => $izin
        ]);
    }
}
