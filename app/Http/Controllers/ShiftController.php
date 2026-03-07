<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    // 1. TAMBAHKAN INI: Untuk nampilin semua Master Shift di bagian atas UI
    public function index()
    {
        $shifts = Shift::with('hariKerja')->get();
        return response()->json($shifts);
    }

    // 2. TAMBAHKAN INI: Untuk simpan Master Shift baru (Opsi B)
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
        ]);

        try {
            $shift = Shift::create([
                'nama' => $request->nama,
                'jam_masuk' => $request->jam_masuk,
                'jam_pulang' => $request->jam_pulang,
            ]);

            return response()->json([
                'message' => 'Master Shift berhasil dibuat!',
                'data' => $shift
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $shift = Shift::with('hariKerja')->find($id);

            if (!$shift) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            return response()->json($shift);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Update validasi: bisa update Hari Kerja ATAU Jam Kerja
        $request->validate([
            'hari_kerja' => 'nullable|array',
            'nama' => 'nullable|string',
            'jam_masuk' => 'nullable',
            'jam_pulang' => 'nullable',
        ]);

        $shift = Shift::findOrFail($id);

        DB::beginTransaction();
        try {
            // Update data dasar jika ada
            $shift->update($request->only(['nama', 'jam_masuk', 'jam_pulang']));

            // Update hari kerja jika dikirim
            if ($request->has('hari_kerja')) {
                $shift->hariKerja()->delete();
                foreach ($request->hari_kerja as $h) {
                    $formattedDay = $this->dayMap($h);
                    $shift->hariKerja()->create([
                        'hari' => $formattedDay
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Berhasil diperbarui!',
                'data' => $shift->load('hariKerja')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 3. TAMBAHKAN INI: Biar admin bisa hapus shift yang salah input
    public function destroy($id)
    {
        if ($id == 1) {
            return response()->json(['message' => 'Shift utama tidak boleh dihapus!'], 403);
        }

        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Master Shift berhasil dihapus']);
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
            throw new \Exception("Hari '$day' tidak valid.");
        }
        return $days[$lowerDay];
    }
}
