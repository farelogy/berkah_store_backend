<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksis';

    protected $fillable = [
        'kode_transaksi',
        'id_cabang',
        'id_user',
        'id_pembeli',
        'nama_pembeli_non_member',
        'subtotal',
        'diskon',
        'total_belanja',
        'potong_saldo_pembeli',
        'total_tagihan_akhir',
        'jumlah_bayar',
        'kembalian_tunai',
        'masuk_saldo_pembeli',
        'sisa_piutang',
        'metode_pembayaran',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'total_belanja' => 'decimal:2',
        'potong_saldo_pembeli' => 'decimal:2',
        'total_tagihan_akhir' => 'decimal:2',
        'jumlah_bayar' => 'decimal:2',
        'kembalian_tunai' => 'decimal:2',
        'masuk_saldo_pembeli' => 'decimal:2',
        'sisa_piutang' => 'decimal:2',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function pembeli(): BelongsTo
    {
        return $this->belongsTo(Pembeli::class, 'id_pembeli');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi');
    }

    public function pembayaranPiutangs(): HasMany
    {
        return $this->hasMany(PembayaranPiutang::class, 'id_transaksi');
    }

    public function returs(): HasMany
    {
        return $this->hasMany(Retur::class, 'id_transaksi');
    }
}
