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

        // 2. Inisialisasi 4 Cabang Berkah Store Pertama jika belum ada
        if (Cabang::count() === 0) {
            $cabangs = [
                [
                    'kode_cabang' => 'CAB-KVL',
                    'nama_cabang' => 'Kavling',
                    'nama_tb' => 'TB Berkah Kavling',
                    'alamat' => 'Jl. Raya Kavling No. 1',
                    'no_telepon' => '081211112222',
                    'no_hp' => '081211112222',
                    'saldo' => 0,
                    'is_active' => true,
                ],
                [
                    'kode_cabang' => 'CAB-THB',
                    'nama_cabang' => 'THB',
                    'nama_tb' => 'TB Berkah THB',
                    'alamat' => 'Taman Harapan Baru Blok A No. 5',
                    'no_telepon' => '081233334444',
                    'no_hp' => '081233334444',
                    'saldo' => 0,
                    'is_active' => true,
                ],
                [
                    'kode_cabang' => 'CAB-LB',
                    'nama_cabang' => 'LB',
                    'nama_tb' => 'TB Berkah LB',
                    'alamat' => 'Jl. Raya Lemahabang No. 88',
                    'no_telepon' => '081255556666',
                    'no_hp' => '081255556666',
                    'saldo' => 0,
                    'is_active' => true,
                ],
                [
                    'kode_cabang' => 'CAB-PS',
                    'nama_cabang' => 'PS',
                    'nama_tb' => 'TB Berkah PS',
                    'alamat' => 'Jl. Pasar Baru No. 12',
                    'no_telepon' => '081277778888',
                    'no_hp' => '081277778888',
                    'saldo' => 0,
                    'is_active' => true,
                ],
            ];

            foreach ($cabangs as $c) {
                Cabang::create($c);
            }
        }
    }
}
