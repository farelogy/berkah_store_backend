<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailRetur extends Model
{
    use HasFactory;

    protected $table = 'detail_returs';

    protected $fillable = [
        'id_retur',
        'id_barang',
        'jumlah',
        'harga_satuan',
        'subtotal',
        'kondisi_barang',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function retur()
    {
        return $this->belongsTo(Retur::class, 'id_retur');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
