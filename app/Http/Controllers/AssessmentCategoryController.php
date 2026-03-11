<?php

namespace App\Http\Controllers;

use App\Models\AssessmentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentCategoryController extends Controller
{
    public function index()
    {
        $categories = AssessmentCategory::where('is_active', true)->get();
        return response()->json($categories);
    }

    // 2. Tambah Kategori (Admin)
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang boleh menambah kategori penilaian!'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = AssessmentCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => 'Karyawan', // Default
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Kategori penilaian berhasil ditambahkan',
            'data' => $category
        ], 201);
    }

    // 3. Update Kategori (Khusus Admin)
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang boleh mengedit kategori!'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $category = AssessmentCategory::findOrFail($id);
        $category->update($request->all());

        return response()->json([
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ]);
    }

    // 4. Hapus Kategori (Khusus Admin)
    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya Admin yang boleh menghapus kategori!'], 403);
        }

        $category = AssessmentCategory::findOrFail($id);
        // Saran: Daripada dihapus permanen, mending di non-aktifkan (Soft Delete / is_active = false)
        // Biar history nilai lama nggak error/hilang.
        $category->update(['is_active' => false]);

        return response()->json([
            'message' => 'Kategori berhasil dinonaktifkan (disembunyikan dari form)'
        ]);
    }
}