<?php

namespace App\Http\Controllers;

use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssessmentQuestionController extends Controller
{
    /**
     * @brief Mengambil semua pertanyaan berdasarkan ID kategori.
     * 
     * @param  int $categoryId ID Kategori Penilaian.
     * @return \Illuminate\Http\JsonResponse Daftar pertanyaan dalam kategori tersebut.
     */
    public function getByCategory($categoryId)
    {
        $questions = AssessmentQuestion::where('category_id', $categoryId)->get();
        return response()->json($questions);
    }

    /**
     * @brief Menyimpan pertanyaan baru ke kategori tertentu.
     * 
     * @param  \Illuminate\Http\Request $request Data [category_id, question_text].
     * @return \Illuminate\Http\JsonResponse status sukses dan data pertanyaan.
     * 
     * @retval 201 Berhasil membuat pertanyaan.
     * @retval 422 Validasi gagal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'   => 'required|exists:assessment_categories,id',
            'question_text' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $question = AssessmentQuestion::create([
            'category_id'   => $request->category_id,
            'question_text' => $request->question_text,
            'is_active'     => true
        ]);

        return response()->json(['message' => 'Pertanyaan berhasil ditambah', 'data' => $question], 201);
    }

    /**
     * @brief Memperbarui teks atau status aktif pertanyaan.
     * 
     * @param  \Illuminate\Http\Request $request Data update [question_text, is_active].
     * @param  int $id ID Pertanyaan.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $question = AssessmentQuestion::findOrFail($id);
        $question->update($request->only(['question_text', 'is_active']));

        return response()->json(['message' => 'Pertanyaan berhasil diupdate', 'data' => $question]);
    }

    /**
     * @brief Menghapus pertanyaan secara permanen.
     * 
     * @param  int $id ID Pertanyaan.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $question = AssessmentQuestion::findOrFail($id);
        $question->delete();

        return response()->json(['message' => 'Pertanyaan berhasil dihapus']);
    }
}