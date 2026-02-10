<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // Tambahkan ini

class UserController extends Controller
{
    public function index()
    {
        // Ambil ID user yang sedang login
        $currentId = Auth::id(); 

        // Ambil semua user kecuali yang sedang login
        return response()->json(User::where('id', '!=', $currentId)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nip'      => 'required|unique:users,nip',
            'name'     => 'required|string',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,karyawan',
        ]);

        $data['password'] = Hash::make($request->password);
        $data['is_active'] = true; // Default aktif sesuai migrasi lo tadi
        
        $user = User::create($data);
        return response()->json(['success' => true, 'user' => $user], 201);
    }

    // ... method update dan destroy tetap sama
}