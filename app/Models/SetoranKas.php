<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetoranKas extends Model
{
    use HasFactory;

    protected $table = 'setoran_kas';

    protected $fillable = [
        'kode_setoran',
        'id_cabang',
        'id_user_penyetor',
        'jumlah',
        'metode',
        'bukti_transfer',
        'id_user_penerima',
        'status',
        'catatan',
        'confirmed_at',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function penyetor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_penyetor');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_penerima');
    }
}
