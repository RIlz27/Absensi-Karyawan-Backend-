<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Kantor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SetupController extends Controller
{
    /**
     * Handle the initial system setup.
     * This endpoint is PUBLIC but protected by a one-time guard:
     * it cannot run if an admin already exists.
     */
    public function store(Request $request)
    {
        // --- SECURITY GUARD: Only allow if no admin exists ---
        if (User::where('role', 'admin')->exists()) {
            return response()->json([
                'message' => 'Setup sudah dilakukan. Silakan login.'
            ], 403);
        }

        // --- VALIDATION ---
        $request->validate([
            // Step 1: Kantor
            'kantor.nama'            => 'required|string|max:255',
            'kantor.alamat'          => 'required|string',
            'kantor.latitude'        => 'required|numeric',
            'kantor.longitude'       => 'required|numeric',
            'kantor.radius_meter'    => 'required|integer|min:10',
            'kantor.toleransi_menit' => 'required|integer|min:0',

            // Step 2: Shift
            'shift.nama'             => 'required|string|max:255',
            'shift.jam_masuk'        => 'required|date_format:H:i',
            'shift.jam_pulang'       => 'required|date_format:H:i',
            'shift.hari_kerja'       => 'required|array|min:1',
            'shift.hari_kerja.*'     => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',

            // Step 3: Admin
            'admin.name'             => 'required|string|max:255',
            'admin.nip'              => 'required|string|unique:users,nip',
            'admin.password'         => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Kantor (Office)
            $kantor = Kantor::create([
                'nama'            => $request->input('kantor.nama'),
                'alamat'          => $request->input('kantor.alamat'),
                'latitude'        => $request->input('kantor.latitude'),
                'longitude'       => $request->input('kantor.longitude'),
                'radius_meter'    => $request->input('kantor.radius_meter'),
                'toleransi_menit' => $request->input('kantor.toleransi_menit'),
            ]);

            // 2. Create Default Shift (with working days)
            $shift = Shift::create([
                'nama'       => $request->input('shift.nama'),
                'jam_masuk'  => $request->input('shift.jam_masuk') . ':00',
                'jam_pulang' => $request->input('shift.jam_pulang') . ':00',
                'warna'      => '#3B82F6',
            ]);

            // Save the selected working days into shift_hari
            foreach ($request->input('shift.hari_kerja') as $hari) {
                $shift->hariKerja()->create(['hari' => $hari]);
            }

            // 3. Create the first Admin User
            $admin = User::create([
                'name'      => $request->input('admin.name'),
                'nip'       => $request->input('admin.nip'),
                'password'  => Hash::make($request->input('admin.password')),
                'role'      => 'admin',
                'kantor_id' => $kantor->id,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Setup berhasil! Silakan login dengan akun admin yang telah dibuat.',
                'data' => [
                    'kantor' => $kantor,
                    'shift'  => $shift->load('hariKerja'),
                    'admin'  => $admin->only(['id', 'name', 'nip', 'role']),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Setup gagal',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if setup has already been completed.
     * Frontend can call this to decide whether to show the setup wizard.
     */
    public function check()
    {
        $isSetupDone = User::where('role', 'admin')->exists();
        return response()->json([
            'setup_done' => $isSetupDone
        ]);
    }

    /**
     * Reset all setup data to allow re-configuration.
     */
    public function reset()
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
            
            \App\Models\User::truncate();
            \App\Models\Kantor::truncate();
            \App\Models\Shift::truncate();
            \Illuminate\Support\Facades\DB::table('shift_haris')->truncate();
            
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Semua data berhasil direset. Silakan setup ulang.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal mereset data', 'error' => $e->getMessage()], 500);
        }
    }
}
