<?php

namespace App\Http\Controllers;

use App\Models\User; // Wajib di-import
use App\Models\Shift; // Wajib di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserShiftController extends Controller
{
    public function assignShift(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'shifts'   => 'required|array', 
        ]);

        $user = User::findOrFail($request->user_id);

        try {
            DB::beginTransaction();

            // Kita siapkan data untuk sync
            // Format yang diminta sync: [shift_id => ['kolom_pivot' => 'isi']]
            $syncData = [];
            foreach ($request->shifts as $s) {
                $syncData[$s['shift_id']] = [
                    'hari'      => $s['hari'],
                    'kantor_id' => $s['kantor_id']
                ];
            }

            // sync() bakal hapus relasi lama dan ganti dengan yang baru
            // Kalau mau nambah tanpa hapus yang lama, pake syncWithoutDetaching()
            $user->shifts()->sync($syncData);

            DB::commit();
            return response()->json(['message' => 'Shift berhasil di-assign ke karyawan!']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal assign shift: ' . $e->getMessage()], 500);
        }
    }
}