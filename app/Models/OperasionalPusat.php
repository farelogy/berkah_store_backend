<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperasionalPusat extends Model
{
    use HasFactory;

    protected $table = 'operasional_pusats';

    protected $fillable = [
        'id_user',
        'kategori',
        'keterangan',
        'jumlah',
        'status',
        'bukti_foto',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
