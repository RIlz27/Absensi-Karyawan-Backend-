<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['nama_shift', 'jam_masuk', 'jam_pulang'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_shifts');
    }
}