<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokBarang extends Model
{
    use HasFactory;

    protected $table = 'stok_barangs';

    protected $fillable = [
        'id_barang',
        'id_cabang',
        'is_gudang_pusat',
        'stok',
    ];

    protected $casts = [
        'is_gudang_pusat' => 'boolean',
        'stok' => 'integer',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }
}
