<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawans';

    protected $fillable = [
        'nama_karyawan',
        'jabatan',
        'no_hp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function kasHarians()
    {
        return $this->hasMany(KasHarian::class, 'id_karyawan');
    }
}
