<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Ambil ID user yang sedang login supaya nggak ngatur jadwal diri sendiri
        $currentId = Auth::id(); 

        // Ambil user kecuali diri sendiri, dan urutkan berdasarkan yang terbaru
        $users = User::where('id', '!=', $currentId)
                    ->orderBy('name', 'asc')
                    ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        // Validasi ditambahin email biar nggak error database
        $validated = $request->validate([
            'nip'      => 'required|unique:users,nip',
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email', // Wajib ada email bro
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,karyawan',
        ]);

        // Mapping data
        $user = User::create([
            'nip'       => $validated['nip'],
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'password'  => Hash::make($validated['password']),
            'is_active' => true, // Sesuai default migrasi lo
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Karyawan berhasil didaftarkan!',
            'user'    => $user
        ], 201);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Proteksi jangan sampai hapus diri sendiri via API
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Gak bisa hapus akun sendiri bro!'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Karyawan berhasil dihapus']);
    }
}