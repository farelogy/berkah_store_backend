<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keranjangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_barang')->constrained('barangs')->cascadeOnDelete();
            $table->integer('jumlah')->default(1);
            $table->decimal('harga_satuan', 15, 2)->comment('Harga Satuan Hasil Negosiasi Kasir');
            $table->foreignId('id_user_last_modified')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id_cabang', 'id_barang']);
        });

        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 50)->unique();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete()->comment('Kasir yang mengeksekusi bayar');
            $table->foreignId('id_pembeli')->nullable()->constrained('pembelis')->nullOnDelete()->comment('Null jika Pelanggan Umum');
            $table->string('nama_pembeli_non_member')->nullable()->comment('Nama catatan struk jika pembeli umum');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('total_belanja', 15, 2);
            $table->decimal('potong_saldo_pembeli', 15, 2)->default(0);
            $table->decimal('total_tagihan_akhir', 15, 2);
            $table->decimal('jumlah_bayar', 15, 2);
            $table->decimal('kembalian_tunai', 15, 2)->default(0);
            $table->decimal('masuk_saldo_pembeli', 15, 2)->default(0)->comment('Uang lebih yang dititipkan ke saldo');
            $table->decimal('sisa_piutang', 15, 2)->default(0)->comment('Dipertahankan secara atomik untuk monitoring');
            $table->string('metode_pembayaran', 30)->default('Tunai')->comment('Tunai, Transfer, QRIS, Piutang Murni, Campuran');
            $table->enum('status', ['Lunas', 'Belum Lunas', 'Batal'])->default('Lunas');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['id_cabang', 'created_at']);
            $table->index(['id_pembeli', 'status']);
        });

        Schema::create('detail_transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transaksi')->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('id_barang')->constrained('barangs')->cascadeOnDelete();
            $table->string('nama_barang_snapshot');
            $table->string('satuan_snapshot', 30);
            $table->decimal('modal_satuan_snapshot', 15, 2)->comment('HPP saat transaksi untuk laba bersih akurat');
            $table->decimal('harga_satuan', 15, 2)->comment('Harga jual riil kesepakatan');
            $table->integer('jumlah');
            $table->integer('jumlah_direfund')->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index(['id_transaksi', 'id_barang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksis');
        Schema::dropIfExists('transaksis');
        Schema::dropIfExists('keranjangs');
    }
};
