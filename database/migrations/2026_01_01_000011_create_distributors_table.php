<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->string('nama_distributor');
            $table->string('kontak_person')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->decimal('total_utang', 15, 2)->default(0)->comment('Total Utang Dagang Berjalan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('nota_distributors', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_nota', 80)->unique();
            $table->foreignId('id_distributor')->constrained('distributors')->cascadeOnDelete();
            $table->date('tanggal_nota');
            $table->decimal('total_tagihan', 15, 2);
            $table->decimal('total_dibayar', 15, 2)->default(0);
            $table->decimal('sisa_utang', 15, 2);
            $table->enum('status', ['Belum Lunas', 'Sebagian', 'Lunas'])->default('Belum Lunas');
            $table->text('catatan')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['id_distributor', 'status']);
        });

        Schema::create('detail_nota_distributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_nota_distributor')->constrained('nota_distributors')->cascadeOnDelete();
            $table->string('nama_barang_nota')->comment('Bebas diketik persis seperti fisik kertas faktur supplier');
            $table->integer('jumlah');
            $table->string('satuan', 30)->nullable();
            $table->decimal('harga_beli_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index('id_nota_distributor');
        });

        Schema::create('pembayaran_distributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_nota_distributor')->constrained('nota_distributors')->cascadeOnDelete();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->decimal('jumlah_bayar', 15, 2);
            $table->date('tanggal_bayar');
            $table->string('metode_pembayaran', 30)->default('Transfer Bank');
            $table->string('bukti_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['id_nota_distributor', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_distributors');
        Schema::dropIfExists('detail_nota_distributors');
        Schema::dropIfExists('nota_distributors');
        Schema::dropIfExists('distributors');
    }
};
