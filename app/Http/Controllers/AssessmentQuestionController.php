<?php

namespace App\Http\Controllers;

use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssessmentQuestionController extends Controller
{
    // Ambil semua pertanyaan berdasarkan kategori
    public function getByCategory($categoryId)
    {
        $questions = AssessmentQuestion::where('category_id', $categoryId)->get();
        return response()->json($questions);
    }

    // Simpan Pertanyaan Baru
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

    // Update Pertanyaan
    public function update(Request $request, $id)
    {
        $question = AssessmentQuestion::findOrFail($id);
        $question->update($request->only(['question_text', 'is_active']));

        return response()->json(['message' => 'Pertanyaan berhasil diupdate', 'data' => $question]);
    }

    // Hapus Pertanyaan
    public function destroy($id)
    {
        $question = AssessmentQuestion::findOrFail($id);
        $question->delete();

        return response()->json(['message' => 'Pertanyaan berhasil dihapus']);
    }
}