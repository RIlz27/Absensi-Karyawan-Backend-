<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\UserShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserShiftController extends Controller
{
    
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
                // Hapus semua shift biasa lama milik user ini sebelum diisi ulang
                UserShift::where('user_id', $userId)->where('tipe', 'biasa')->delete();

                foreach ($shift->hariKerja as $item) {
                    UserShift::create([
                        'user_id'   => $userId,
                        'hari'      => $item->hari,
                        'tipe'      => 'biasa',
                        'shift_id'  => $shift->id,
                        'kantor_id' => $request->kantor_id
                    ]);
                }
            }
            DB::commit();
            return response()->json(['message' => 'Shift Biasa berhasil diatur mengikuti jadwal default!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function storeShiftTambahan(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'shift_id' => 'required',
            'kantor_id' => 'required',
            'hari'     => 'required' 
        ]);

        Log::info('Data diterima:', $request->all());

        foreach ($request->user_ids as $userId) {
            if (!$userId) {
                Log::error('Gagal simpan: user_id kosong');
                continue;
            }
            \App\Models\UserShift::updateOrCreate(
                [
                    'user_id'   => $userId,
                    'hari'      => $request->hari,
                    'kantor_id' => $request->kantor_id,
                    'tipe'      => 'tambahan',
                ],
                [
                    'shift_id'  => $request->shift_id,
                    'kantor_id' => $request->kantor_id,
                    'tipe'      => 'tambahan',
                ]
            );
        }
        return response()->json(['message' => 'Success'], 201);
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
        // PENTING: eager load relasi shift agar front-end tidak kosong!
        $shifts = UserShift::with('shift')->where('user_id', $id)->get();
        return response()->json($shifts);
    }
}
