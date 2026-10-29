<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasbonKaryawan extends Model
{
    use HasFactory;

    protected $table = 'kasbon_karyawans';

    protected $fillable = [
        'kode_kasbon',
        'id_karyawan',
        'id_cabang',
        'id_user_input',
        'tanggal',
        'nominal',
        'sisa_kasbon',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'sisa_kasbon' => 'decimal:2',
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
