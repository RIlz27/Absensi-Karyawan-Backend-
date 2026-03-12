<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question_text',
        'is_active',
    ];

    // Relasi: Pertanyaan ini milik sebuah Kategori
    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }
}