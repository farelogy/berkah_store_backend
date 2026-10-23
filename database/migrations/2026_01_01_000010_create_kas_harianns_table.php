<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_karyawan');
            $table->string('jabatan', 50)->nullable()->comment('Sopir, Kenek, Gudang, Kasir, Umum');
            $table->string('no_hp', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kas_harianns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_transaksi')->nullable()->constrained('transaksis')->nullOnDelete();
            $table->foreignId('id_pembayaran_piutang')->nullable()->constrained('pembayaran_piutangs')->nullOnDelete();
            $table->foreignId('id_karyawan')->nullable()->constrained('karyawans')->nullOnDelete();
            $table->string('kategori', 50)->comment('Penjualan Tunai, Pembayaran Piutang, Titipan Saldo, Uang Makan, Bensin Armada, Listrik & Air, Perlengkapan Toko, Operasional Lainnya, Setoran Kas');
            $table->enum('tipe', ['Masuk', 'Keluar']);
            $table->decimal('jumlah', 15, 2);
            $table->text('keterangan')->nullable();
            $table->string('foto_bukti')->nullable();
            $table->timestamps();

            $table->index(['id_cabang', 'created_at', 'tipe']);
        });

        Schema::create('setoran_kas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_setoran', 50)->unique();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user_penyetor')->constrained('users')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->enum('metode', ['Tunai', 'Transfer Bank'])->default('Tunai');
            $table->string('bukti_transfer')->nullable();
            $table->foreignId('id_user_penerima')->nullable()->constrained('users')->nullOnDelete()->comment('Admin Pusat yang klik konfirmasi');
            $table->enum('status', ['Menunggu Konfirmasi', 'Terverifikasi', 'Ditolak'])->default('Menunggu Konfirmasi');
            $table->text('catatan')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['id_cabang', 'status']);
        });

        Schema::create('tutup_kasirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('saldo_awal', 15, 2);
            $table->decimal('total_masuk', 15, 2);
            $table->decimal('total_keluar', 15, 2);
            $table->decimal('saldo_sistem', 15, 2);
            $table->decimal('saldo_fisik_riil', 15, 2);
            $table->decimal('selisih', 15, 2)->default(0);
            $table->text('catatan_selisih')->nullable();
            $table->timestamps();

            $table->unique(['id_cabang', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutup_kasirs');
        Schema::dropIfExists('setoran_kas');
        Schema::dropIfExists('kas_harianns');
        Schema::dropIfExists('karyawans');
    }
};
