<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranDistributor extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_distributors';

    protected $fillable = [
        'id_nota_distributor',
        'id_user',
        'jumlah_bayar',
        'tanggal_bayar',
        'metode_pembayaran',
        'bukti_bayar',
        'catatan',
    ];

    protected $casts = [
        'jumlah_bayar' => 'decimal:2',
        'tanggal_bayar' => 'date',
    ];

    public function nota()
    {
        return $this->belongsTo(NotaDistributor::class, 'id_nota_distributor');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
