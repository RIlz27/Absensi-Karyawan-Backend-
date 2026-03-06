<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftHari extends Model
{
    protected $table = 'shift_hari'; 

    protected $fillable = ['shift_id', 'hari'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}