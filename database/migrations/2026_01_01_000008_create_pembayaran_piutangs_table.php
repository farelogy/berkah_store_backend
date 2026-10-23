<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_piutangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pembayaran', 50)->unique();
            $table->foreignId('id_cabang')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete()->comment('Kasir yang menerima uang');
            $table->foreignId('id_pembeli')->constrained('pembelis')->cascadeOnDelete();
            $table->foreignId('id_transaksi')->nullable()->constrained('transaksis')->nullOnDelete()->comment('Null jika pembayaran bulk multi-nota');
            $table->decimal('jumlah_bayar', 15, 2)->comment('Uang tunai / transfer yang diterima fisik');
            $table->decimal('potong_saldo_pembeli', 15, 2)->default(0);
            $table->decimal('total_dialokasikan', 15, 2)->comment('Total yang dipakai melunasi tagihan');
            $table->decimal('kelebihan_masuk_saldo', 15, 2)->default(0);
            $table->string('metode_pembayaran', 30)->default('Tunai');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['id_cabang', 'created_at']);
            $table->index(['id_pembeli', 'id_transaksi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_piutangs');
    }
};
