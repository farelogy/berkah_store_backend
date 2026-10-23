<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailNotaDistributor extends Model
{
    use HasFactory;

    protected $table = 'detail_nota_distributors';

    protected $fillable = [
        'id_nota_distributor',
        'nama_barang_nota',
        'jumlah',
        'satuan',
        'harga_beli_satuan',
        'subtotal',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_beli_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function nota()
    {
        return $this->belongsTo(NotaDistributor::class, 'id_nota_distributor');
    }
}
