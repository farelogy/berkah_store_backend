<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GajiKaryawan extends Model
{
    use HasFactory;

    protected $table = 'gaji_karyawans';

    protected $fillable = [
        'kode_gaji',
        'id_karyawan',
        'id_cabang',
        'id_user_admin',
        'bulan',
        'tahun',
        'gaji_harian',
        'total_hari_kerja',
        'total_gaji_kotor',
        'total_uang_makan',
        'total_kasbon',
        'bonus_tambahan',
        'total_gaji_bersih',
        'status',
        'tanggal_bayar',
        'metode_bayar',
        'catatan',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'gaji_harian' => 'decimal:2',
        'total_hari_kerja' => 'decimal:1',
        'total_gaji_kotor' => 'decimal:2',
        'total_uang_makan' => 'decimal:2',
        'total_kasbon' => 'decimal:2',
        'bonus_tambahan' => 'decimal:2',
        'total_gaji_bersih' => 'decimal:2',
        'tanggal_bayar' => 'datetime',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function userAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_admin');
    }
}
