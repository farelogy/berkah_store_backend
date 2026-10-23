<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    use HasFactory;

    protected $table = 'mutasi_stoks';

    protected $fillable = [
        'kode_mutasi',
        'id_cabang_tujuan',
        'id_user_request',
        'id_barang',
        'jumlah_minta',
        'jumlah_kirim',
        'jumlah_diterima',
        'sumber_tipe',
        'id_cabang_asal',
        'id_user_dispatcher',
        'id_user_penerima',
        'status',
        'catatan',
        'dispatched_at',
        'completed_at',
    ];

    protected $casts = [
        'jumlah_minta' => 'integer',
        'jumlah_kirim' => 'integer',
        'jumlah_diterima' => 'integer',
        'dispatched_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function cabangTujuan()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_tujuan');
    }

    public function cabangAsal()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang_asal');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function userRequest()
    {
        return $this->belongsTo(User::class, 'id_user_request');
    }

    public function userDispatcher()
    {
        return $this->belongsTo(User::class, 'id_user_dispatcher');
    }

    public function userPenerima()
    {
        return $this->belongsTo(User::class, 'id_user_penerima');
    }
}
