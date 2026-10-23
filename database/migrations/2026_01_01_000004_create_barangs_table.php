<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 50)->nullable()->unique();
            $table->string('nama_barang');
            $table->string('kategori')->default('Umum');
            $table->string('satuan', 30)->default('Pcs');
            $table->decimal('modal', 15, 2)->default(0)->comment('Harga Beli / HPP Standar');
            $table->decimal('harga', 15, 2)->default(0)->comment('Harga Jual Standar Toko');
            $table->integer('minimal_stok')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['nama_barang', 'kategori']);
        });

        Schema::create('stok_barangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang')->constrained('barangs')->cascadeOnDelete();
            $table->foreignId('id_cabang')->nullable()->constrained('cabangs')->cascadeOnDelete()->comment('Null jika di Gudang Pusat');
            $table->boolean('is_gudang_pusat')->default(false);
            $table->integer('stok')->default(0);
            $table->timestamps();

            $table->unique(['id_barang', 'id_cabang', 'is_gudang_pusat']);
        });

        Schema::create('riwayat_stoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang')->constrained('barangs')->cascadeOnDelete();
            $table->foreignId('id_cabang')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('id_user')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('jumlah')->comment('Positif (+) Tambah, Negatif (-) Kurang');
            $table->integer('stok_akhir');
            $table->string('tipe', 50)->comment('Penjualan, Retur Masuk, Retur Rusak, Mutasi Keluar, Mutasi Masuk, Restock, Stock Opname');
            $table->string('alasan')->nullable();
            $table->string('referensi_kode', 50)->nullable();
            $table->timestamps();

            $table->index(['id_barang', 'id_cabang', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_stoks');
        Schema::dropIfExists('stok_barangs');
        Schema::dropIfExists('barangs');
    }
};
