<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'category_id',
        'score',
    ];

    // Relasi balik ke Header Penilaian
    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    // Relasi ke Master Kategori 
    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }
}