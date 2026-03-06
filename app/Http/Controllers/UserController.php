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
        $currentId = Auth::id();
        $users = User::with(['kantor', 'shifts'])
            ->where('id', '!=', $currentId)
            ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nip'  => 'required|unique:users,nip',
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,karyawan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }
        $user = User::create([
            'nip'       => $request->nip,
            'name'      => $request->name,
            'role'      => $request->role,
            'password'  => Hash::make($request->nip),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil didaftarkan! Password default adalah NIP.',
            'user'    => $user
        ], 201);
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
