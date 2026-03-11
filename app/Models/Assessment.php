<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluator_id',
        'evaluatee_id',
        'assessment_date',
        'period_type', 
        'period_name',   
        'general_notes',
        'is_visible',    
    ];

    // Relasi Penilai
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    // Rekasi Karyawan
    public function evaluatee()
    {
        return $this->belongsTo(User::class, 'evaluatee_id');
    }

    // Relasi ke Detail Nilainya 
    public function details()
    {
        return $this->hasMany(AssessmentDetail::class, 'assessment_id');
    }
}