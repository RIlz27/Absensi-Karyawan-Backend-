<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    protected $fillable = [
        'kode',
        'kantor_id',
        'type',    
        'is_active',
        'expired_at',
    ];
}
