<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserShiftController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'shift_id'  => 'required|exists:shifts,id',
            'kantor_id' => 'required|exists:kantors,id',
            'hari'      => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        ]);

        // Simpan atau update jadwal
        $userShift = \App\Models\UserShift::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'hari'    => $request->hari,
            ],
            [
                'shift_id'  => $request->shift_id,
                'kantor_id' => $request->kantor_id,
            ]
        );

        return response()->json(['message' => 'Jadwal berhasil diperbarui!', 'data' => $userShift]);
    }
    public function destroy($id)
    {
        $deleted = \App\Models\UserShift::where('id', $id)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Jadwal berhasil dihapus!']);
        }

        return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
    }
}
