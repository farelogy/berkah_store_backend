<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksi extends Model
{
    use HasFactory;

    protected $table = 'detail_transaksis';

    protected $fillable = [
        'id_transaksi',
        'id_barang',
        'nama_barang_snapshot',
        'satuan_snapshot',
        'modal_satuan_snapshot',
        'harga_satuan',
        'jumlah',
        'jumlah_direfund',
        'subtotal',
    ];

    protected $casts = [
        'modal_satuan_snapshot' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'jumlah' => 'integer',
        'jumlah_direfund' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
