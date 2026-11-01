<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembeli extends Model
{
    use HasFactory;

    protected $table = 'pembelis';

    protected $fillable = [
        'nama_pembeli',
        'no_hp',
        'alamat',
        'id_cabang_pendaftaran',
        'saldo',
        'sisa_piutang',
        'limit_piutang',
        'is_active',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'sisa_piutang' => 'decimal:2',
        'limit_piutang' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_pendaftaran');
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_pembeli');
    }

    public function pembayaranPiutangs(): HasMany
    {
        return $this->hasMany(PembayaranPiutang::class, 'id_pembeli');
    }
}
