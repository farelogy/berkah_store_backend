<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlaimBarangRusak extends Model
{
    use HasFactory;

    protected $table = 'klaim_barang_rusaks';

    protected $fillable = [
        'kode_klaim',
        'id_cabang',
        'id_barang',
        'id_distributor',
        'jumlah',
        'status',
        'catatan',
    ];

    protected $casts = [
        'jumlah' => 'integer',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function distributor()
    {
        return $this->belongsTo(Distributor::class, 'id_distributor');
    }
}
