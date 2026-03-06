<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function show($id)
    {
        try {
            // Kita panggil relasi hariKerja
            $shift = Shift::with('hariKerja')->find($id);

            if (!$shift) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            // Kita ubah manual ke array biar key-nya beneran 'hari_kerja'
            $data = $shift->toArray();

            // Pastikan key-nya adalah 'hari_kerja' sesuai yang dicari frontend
            if (isset($data['hari_kerja'])) {
                return response()->json($data);
            }

            // Fallback kalau Laravel tetep ngirim camelCase
            return response()->json([
                'id' => $shift->id,
                'nama' => $shift->nama,
                'jam_masuk' => $shift->jam_masuk,
                'jam_pulang' => $shift->jam_pulang,
                'hari_kerja' => $shift->hariKerja // Paksa key ini
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // 1. Validasi Input biar gak kosong
        $request->validate([
            'hari_kerja' => 'required|array',
        ]);

        $shift = Shift::findOrFail($id);

        DB::beginTransaction();
        try {
            // 2. Hapus data lama
            $shift->hariKerja()->delete();

            // 3. Mapping dan Simpan
            foreach ($request->hari_kerja as $h) {
                // Gunakan dayMap biar input 'senin' atau 'monday' tetep masuk bener ke Enum DB
                $formattedDay = $this->dayMap($h);

                $shift->hariKerja()->create([
                    'hari' => $formattedDay
                ]);
            }

            DB::commit();

            // 4. Return data terbaru biar Frontend bisa refresh state tanpa reload manual
            return response()->json([
                'message' => 'Berhasil simpan!',
                'data' => $shift->load('hariKerja')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function dayMap($day)
    {
        $days = [
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
            'sunday'    => 'Sunday',
            // Tambahin mapping bahasa Indonesia biar kalo React lupa convert, Backend yg handle
            'senin'     => 'Monday',
            'selasa'    => 'Tuesday',
            'rabu'      => 'Wednesday',
            'kamis'     => 'Thursday',
            'jumat'     => 'Friday',
            'sabtu'     => 'Saturday',
            'minggu'    => 'Sunday',
        ];

        $lowerDay = strtolower(trim($day));

        if (!isset($days[$lowerDay])) {
            throw new \Exception("Hari '$day' tidak valid untuk database.");
        }

        return $days[$lowerDay];
    }
}
