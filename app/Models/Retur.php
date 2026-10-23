<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retur extends Model
{
    use HasFactory;

    protected $table = 'returs';

    protected $fillable = [
        'kode_retur',
        'id_transaksi',
        'id_cabang',
        'id_user',
        'tipe_retur',
        'total_nilai_retur',
        'total_nilai_pengganti',
        'selisih_nominal',
        'status_penyelesaian',
        'keterangan',
    ];

    protected $casts = [
        'total_nilai_retur' => 'decimal:2',
        'total_nilai_pengganti' => 'decimal:2',
        'selisih_nominal' => 'decimal:2',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function details()
    {
        return $this->hasMany(DetailRetur::class, 'id_retur');
    }
}
