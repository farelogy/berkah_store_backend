<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasPusat extends Model
{
    use HasFactory;

    protected $table = 'kas_pusats';

    protected $fillable = [
        'saldo',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
    ];
}
