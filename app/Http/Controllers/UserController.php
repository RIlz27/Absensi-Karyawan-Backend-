<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['kantor', 'shifts'])->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip'  => 'required|unique:users,nip',
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,karyawan,manager',
            'kantor_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'nip'       => $request->nip,
            'name'      => $request->name,
            'role'      => $request->role,
            'kantor_id' => $request->kantor_id,
            'password'  => Hash::make($request->nip),
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Berhasil!'], 201);
    }

    public function updateRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,karyawan,manager',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Role tidak valid'], 422);
        }

        $user = User::findOrFail($id);
        
        // Cek jika mencoba mengubah role sendiri menjadi selain admin (bisa menyebabkan lockout)
        if ($user->id === Auth::id() && $request->role !== 'admin') {
             return response()->json(['message' => 'Tidak dapat mengubah role akun sendiri menjadi selain Admin'], 403);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Role karyawan berhasil diubah',
            'user' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:admin,karyawan,manager',
            'kantor_id' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['name', 'role', 'kantor_id']));

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diupdate',
            'user' => $user->load('kantor')
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Tidak Dapat Menghapus akun Sendiri'], 403);
        }
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil dihapus'
        ]);
    }
}
