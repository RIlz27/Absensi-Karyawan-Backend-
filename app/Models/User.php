<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // 1. Tambahkan import ini

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nip',
        'kantor_id',
        'is_active',
        'avatar',
        'points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function kantor()
    {
        return $this->belongsTo(Kantor::class , 'kantor_id');
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class , 'user_shifts')
            ->withPivot('hari', 'kantor_id')
            ->withTimestamps();
    }

    public function pointHistories()
    {
        return $this->hasMany(PointHistory::class);
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }

    public function izins()
    {
        return $this->hasMany(Izin::class);
    }

    public function cutis()
    {
        return $this->hasMany(Cuti::class);
    }

    // Siapa Penilai?
    public function assessmentsGiven()
    {
        return $this->hasMany(Assessment::class, 'evaluator_id');
    }

    // Nilai apa saja?
    public function assessmentsReceived()
    {
        return $this->hasMany(Assessment::class, 'evaluatee_id');
    }
}
