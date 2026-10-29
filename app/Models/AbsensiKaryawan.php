<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiKaryawan extends Model
{
    use HasFactory;

    protected $table = 'absensi_karyawans';

    protected $fillable = [
        'id_karyawan',
        'id_cabang',
        'id_user_input',
        'tanggal',
        'status',
        'nilai_hari',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nilai_hari' => 'decimal:1',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function userInput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_input');
    }
}
