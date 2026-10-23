<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'role',
        'id_cabang',
        'is_active',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCabang(): bool
    {
        return $this->role === 'cabang';
    }

    public function isDireksi(): bool
    {
        return $this->role === 'direksi';
    }

    public function isPending(): bool
    {
        return $this->role === 'pending';
    }
}
