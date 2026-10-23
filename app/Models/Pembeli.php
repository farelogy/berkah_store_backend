<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembeli extends Model
{
    use HasFactory;

    protected $table = 'pembelis';

    protected $fillable = [
        'nama_pembeli',
        'no_hp',
        'alamat',
        'tipe_pelanggan',
        'saldo',
        'total_piutang',
        'is_active',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'total_piutang' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_pembeli');
    }

    public function pembayaranPiutangs(): HasMany
    {
        return $this->hasMany(PembayaranPiutang::class, 'id_pembeli');
    }
}
