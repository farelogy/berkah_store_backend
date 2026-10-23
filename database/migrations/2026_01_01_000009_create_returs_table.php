<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_retur', 50)->unique();
            $table->foreignId('id_transaksi')->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->enum('tipe_retur', ['Murni Retur', 'Tukar Barang'])->default('Tukar Barang');
            $table->decimal('total_nilai_retur', 15, 2);
            $table->decimal('total_nilai_pengganti', 15, 2)->default(0);
            $table->decimal('selisih_nominal', 15, 2)->default(0)->comment('Positif: Kurang bayar (pembeli nambah), Negatif: Lebih bayar (toko kembali uang/saldo)');
            $table->string('status_penyelesaian', 40)->default('Selesai');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['id_cabang', 'created_at']);
        });

        Schema::create('detail_returs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_retur')->constrained('returs')->cascadeOnDelete();
            $table->foreignId('id_barang')->constrained('barangs')->cascadeOnDelete();
            $table->integer('jumlah');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->enum('kondisi_barang', ['Layak Jual', 'Rusak Klaim Distributor'])->default('Layak Jual');
            $table->timestamps();
        });

        Schema::create('klaim_barang_rusaks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_klaim', 50)->unique();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_barang')->constrained('barangs')->cascadeOnDelete();
            $table->unsignedBigInteger('id_distributor')->nullable();
            $table->integer('jumlah');
            $table->enum('status', [
                'Dilaporkan Cabang', 
                'Diajukan ke Distributor', 
                'Selesai Ganti Fisik', 
                'Selesai Potong Nota', 
                'Ditolak'
            ])->default('Dilaporkan Cabang');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['id_cabang', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klaim_barang_rusaks');
        Schema::dropIfExists('detail_returs');
        Schema::dropIfExists('returs');
    }
};
