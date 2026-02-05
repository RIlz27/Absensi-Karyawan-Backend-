<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kantor extends Model
{
    protected $table = 'kantors';

    protected $fillable = [
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'radius_meter'
    ];
}
