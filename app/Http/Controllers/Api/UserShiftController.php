<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\UserShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserShiftController extends Controller
{
    // --- 1. HANDLE SHIFT BIASA (CARD UNGU) ---
    // Otomatis ngambil dari template hari kerja
    public function storeShiftBiasa(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'user_ids' => 'required|array',
            'kantor_id' => 'required'
        ]);
        $shift = Shift::with('hariKerja')->findOrFail($request->shift_id);
        $user_ids = $request->user_ids;

        try {
            DB::beginTransaction();
            foreach ($user_ids as $userId) {
                foreach ($shift->hariKerja as $item) {
                    UserShift::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'hari'    => $item->hari,
                            'tipe'    => 'biasa'
                        ],
                        [
                            'shift_id'  => $shift->id,
                            'kantor_id' => $request->kantor_id
                        ]
                    );
                }
            }
            DB::commit();
            return response()->json(['message' => 'Shift Biasa berhasil diplot otomatis!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function storeShiftTambahan(Request $request)
    {
        $request->validate([
            'shift_id' => 'required',
            'user_ids' => 'required|array',
            'hari'     => 'required|string',
            'kantor_id' => 'required'
        ]);

        try {
            DB::beginTransaction();
            UserShift::whereIn('user_id', $request->user_ids)
                ->where('hari', $request->hari)
                ->where('tipe', 'tambahan')
                ->delete();

            foreach ($request->user_ids as $userId) {
                UserShift::create([
                    'user_id'   => $userId,
                    'hari'      => $request->hari,
                    'tipe'      => 'tambahan',
                    'shift_id'  => $request->shift_id,
                    'kantor_id' => $request->kantor_id
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Shift diperbarui!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $deleted = UserShift::where('id', $id)->delete();
        if ($deleted) {
            return response()->json(['message' => 'Jadwal berhasil dihapus!']);
        }
        return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
    }

    public function show($id)
    {
        // Coba return data shift user berdasarkan ID
        $shifts = UserShift::where('user_id', $id)->get();
        return response()->json($shifts);
    }
}
