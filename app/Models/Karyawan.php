<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawans';

    protected $fillable = [
        'nama_karyawan',
        'jabatan',
        'no_hp',
        'gaji_harian',
        'id_cabang',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'gaji_harian' => 'decimal:2',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function kasHarians(): HasMany
    {
        return $this->hasMany(KasHarian::class, 'id_karyawan');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(AbsensiKaryawan::class, 'id_karyawan');
    }

    public function kasbons(): HasMany
    {
        return $this->hasMany(KasbonKaryawan::class, 'id_karyawan');
    }

    public function gajis(): HasMany
    {
        return $this->hasMany(GajiKaryawan::class, 'id_karyawan');
    }
}
