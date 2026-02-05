<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserShift extends Model
{
    protected $table = 'user_shifts';

    protected $fillable = [
        'user_id',
        // 'shift_id',
        'kantor_id',
        'tanggal_mulai',
        'tanggal_selesai'
    ];

    public function kantor()
    {
        return $this->belongsTo(Kantor::class);
    }
}
