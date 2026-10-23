<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasHarian extends Model
{
    use HasFactory;

    protected $table = 'kas_harianns';

    protected $fillable = [
        'id_cabang',
        'id_user',
        'id_transaksi',
        'id_pembayaran_piutang',
        'id_karyawan',
        'kategori',
        'tipe',
        'jumlah',
        'keterangan',
        'foto_bukti',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }

    public function pembayaranPiutang(): BelongsTo
    {
        return $this->belongsTo(PembayaranPiutang::class, 'id_pembayaran_piutang');
    }
}
