<?php

namespace App\Http\Controllers;

use App\Models\AssessmentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentCategoryController extends Controller
{
    /**
     * @brief Mengambil semua kategori penilaian beserta pertanyaannya.
     * 
     * @details Hanya mengambil kategori dan pertanyaan yang berstatus aktif.
     * 
     * @return \Illuminate\Http\JsonResponse Daftar kategori dan pertanyaan.
     */
    public function index()
    {
        $categories = AssessmentCategory::with(['questions' => function ($query) {
            $query->where('is_active', true);
        }])
            ->where('is_active', true)
            ->get();

        return response()->json($categories);
    }

    /**
     * @brief Menambah kategori penilaian baru (Khusus Admin).
     * 
     * @param  \Illuminate\Http\Request $request Data kategori [name, description].
     * @return \Illuminate\Http\JsonResponse
     * 
     * @retval 201 Berhasil membuat kategori.
     * @retval 403 Akses ditolak jika bukan Admin.
     */
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

    /**
     * @brief Memperbarui data kategori (Khusus Admin).
     * 
     * @param  \Illuminate\Http\Request $request Data update.
     * @param  int $id ID Kategori.
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * @brief Menonaktifkan kategori penilaian (Khusus Admin).
     * 
     * @details Kategori tidak dihapus permanen untuk menjaga integritas data 
     *          penilaian lama, melainkan hanya diubah status is_active menjadi false.
     * 
     * @param  int $id ID Kategori.
     * @return \Illuminate\Http\JsonResponse
     */
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
