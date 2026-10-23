<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barangs';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan',
        'modal',
        'harga',
        'minimal_stok',
        'is_active',
    ];

    protected $casts = [
        'modal' => 'decimal:2',
        'harga' => 'decimal:2',
        'minimal_stok' => 'integer',
        'is_active' => 'boolean',
    ];

    public function stokBarangs(): HasMany
    {
        return $this->hasMany(StokBarang::class, 'id_barang');
    }

    public function riwayatStoks(): HasMany
    {
        return $this->hasMany(RiwayatStok::class, 'id_barang');
    }
}
