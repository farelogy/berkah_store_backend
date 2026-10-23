<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranPiutang extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_piutangs';

    protected $fillable = [
        'kode_pembayaran',
        'id_cabang',
        'id_user',
        'id_pembeli',
        'id_transaksi',
        'jumlah_bayar',
        'potong_saldo_pembeli',
        'total_dialokasikan',
        'kelebihan_masuk_saldo',
        'metode_pembayaran',
        'catatan',
    ];

    protected $casts = [
        'jumlah_bayar' => 'decimal:2',
        'potong_saldo_pembeli' => 'decimal:2',
        'total_dialokasikan' => 'decimal:2',
        'kelebihan_masuk_saldo' => 'decimal:2',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class, 'id_pembeli');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }
}
