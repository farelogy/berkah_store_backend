<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distributor extends Model
{
    use HasFactory;

    protected $table = 'distributors';

    protected $fillable = [
        'nama_distributor',
        'kontak_person',
        'no_hp',
        'alamat',
        'total_utang',
        'is_active',
    ];

    protected $casts = [
        'total_utang' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function notas(): HasMany
    {
        return $this->hasMany(NotaDistributor::class, 'id_distributor');
    }
}
