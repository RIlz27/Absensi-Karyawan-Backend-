<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
    ];

    //relasi kategori
    public function assessmentDetails()
    {
        return $this->hasMany(AssessmentDetail::class, 'category_id');
    }
}