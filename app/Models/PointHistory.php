<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'final_score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
