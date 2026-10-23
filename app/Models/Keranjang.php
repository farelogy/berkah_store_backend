<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keranjang extends Model
{
    use HasFactory;

    protected $table = 'keranjangs';

    protected $fillable = [
        'id_cabang',
        'id_barang',
        'jumlah',
        'harga_satuan',
        'id_user_last_modified',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_satuan' => 'decimal:2',
    ];

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function userLastModified(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_last_modified');
    }
}
