<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\KasPusat;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi Rekening / Kas Utama Pusat jika belum ada
        if (KasPusat::count() === 0) {
            KasPusat::create(['saldo' => 0]);
        }

        // 2. Inisialisasi Cabang Utama Pertama jika belum ada
        if (Cabang::count() === 0) {
            Cabang::create([
                'kode_cabang' => 'CAB-01',
                'nama_cabang' => 'Cabang Utama',
                'nama_tb' => 'Toko Bangunan Berkah Utama',
                'alamat' => 'Jl. Raya Utama No. 1',
                'no_hp' => '081234567890',
                'saldo' => 0,
                'is_active' => true,
            ]);
        }
    }
}
