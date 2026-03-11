<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    //pengecekan
    public function getSubordinates()
    {
        $user = Auth::user();

        if ($user->role === 'manager') {
            $bawahan = User::where('role', 'karyawan')
                ->where('kantor_id', $user->kantor_id)
                ->get();
            return response()->json($bawahan);
        }

        if ($user->role === 'admin') {
            $semuaKaryawan = User::where('role', 'karyawan')->get();
            return response()->json($semuaKaryawan);
        }

        return response()->json(['message' => 'Akses ditolak'], 403);
    }

    //simpan nilai & feedback
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, ['manager', 'admin'])) {
            return response()->json(['message' => 'Hanya Manager/Admin yang bisa menilai!'], 403);
        }

        $request->validate([
            'evaluatee_id'  => 'required|exists:users,id',
            'assessment_date' => 'required|date',
            'period_type'   => 'required|in:Harian,Mingguan,Bulanan',
            'period_name'   => 'required|string',
            'general_notes' => 'nullable|string',
            'is_visible'    => 'boolean',

            // Validasi Array Detail Kategori 
            'details'       => 'required|array|min:1',
            'details.*.category_id' => 'required|exists:assessment_categories,id',
            'details.*.score'       => 'required|numeric|min:1|max:10',
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan Header
            $assessment = Assessment::create([
                'evaluator_id'    => Auth::id(),
                'evaluatee_id'    => $request->evaluatee_id,
                'assessment_date' => $request->assessment_date,
                'period_type'     => $request->period_type,
                'period_name'     => $request->period_name,
                'general_notes'   => $request->general_notes,
                'is_visible'      => $request->is_visible ?? true,
            ]);

            // 2. Simpan Detail Nilai 
            foreach ($request->details as $detail) {
                AssessmentDetail::create([
                    'assessment_id' => $assessment->id,
                    'category_id'   => $detail['category_id'],
                    'score'         => $detail['score'],
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Penilaian berhasil disimpan!', 'data' => $assessment->load('details')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan penilaian: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Assessment::with(['evaluator:id,name', 'evaluatee:id,name', 'details.category']);

        if ($user->role === 'karyawan') {
            //hanya karyawan yg bsa liat nilainya
            $query->where('evaluatee_id', $user->id)
                ->where('is_visible', true);
        } elseif ($user->role === 'manager') {
            //manager bisa liat nilai karyawan
            $query->where('evaluator_id', $user->id);
        }

        //periode
        if ($request->period_type) {
            $query->where('period_type', $request->period_type);
        }

        return response()->json($query->latest('assessment_date')->get());
    }

    public function show($id)
    {
        try {
            $assessment = Assessment::with([
                'evaluator',
                'evaluatee',
                'details.category' 
            ])->findOrFail($id);

            return response()->json($assessment);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil detail penilaian',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
