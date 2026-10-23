<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutupKasir extends Model
{
    use HasFactory;

    protected $table = 'tutup_kasirs';

    protected $fillable = [
        'id_cabang',
        'id_user',
        'tanggal',
        'saldo_awal',
        'total_masuk',
        'total_keluar',
        'saldo_sistem',
        'saldo_fisik_riil',
        'selisih',
        'catatan_selisih',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'saldo_awal' => 'decimal:2',
        'total_masuk' => 'decimal:2',
        'total_keluar' => 'decimal:2',
        'saldo_sistem' => 'decimal:2',
        'saldo_fisik_riil' => 'decimal:2',
        'selisih' => 'decimal:2',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
