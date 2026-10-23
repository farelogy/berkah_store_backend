<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStok extends Model
{
    use HasFactory;

    protected $table = 'riwayat_stoks';

    protected $fillable = [
        'id_barang',
        'id_cabang',
        'id_user',
        'jumlah',
        'stok_akhir',
        'tipe',
        'alasan',
        'referensi_kode',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'stok_akhir' => 'integer',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
