<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaDistributor extends Model
{
    use HasFactory;

    protected $table = 'nota_distributors';

    protected $fillable = [
        'nomor_nota',
        'id_distributor',
        'tanggal_nota',
        'total_tagihan',
        'total_dibayar',
        'sisa_utang',
        'status',
        'catatan',
        'paid_at',
    ];

    protected $casts = [
        'tanggal_nota' => 'date',
        'total_tagihan' => 'decimal:2',
        'total_dibayar' => 'decimal:2',
        'sisa_utang' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class, 'id_distributor');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailNotaDistributor::class, 'id_nota_distributor');
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(PembayaranDistributor::class, 'id_nota_distributor');
    }
}
