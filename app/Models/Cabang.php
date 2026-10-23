<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabang extends Model
{
    use HasFactory;

    protected $table = 'cabangs';

    protected $fillable = [
        'kode_cabang',
        'nama_cabang',
        'nama_tb',
        'alamat',
        'no_hp',
        'saldo',
        'is_active',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_cabang');
    }

    public function stokBarangs(): HasMany
    {
        return $this->hasMany(StokBarang::class, 'id_cabang');
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_cabang');
    }

    public function kasHarianns(): HasMany
    {
        return $this->hasMany(KasHarian::class, 'id_cabang');
    }

    public function setoranKas(): HasMany
    {
        return $this->hasMany(SetoranKas::class, 'id_cabang');
    }
}
