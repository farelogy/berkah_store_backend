<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pembeli');
            $table->string('no_hp', 30)->nullable()->index();
            $table->text('alamat')->nullable();
            $table->string('tipe_pelanggan', 50)->default('Langganan')->comment('Mandor, Kontraktor, Toko Mitra, Langganan');
            $table->decimal('saldo', 15, 2)->default(0)->comment('Saldo Deposit Toko (Store Credit)');
            $table->decimal('total_piutang', 15, 2)->default(0)->comment('Total Tagihan Aktif Belum Lunas');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['nama_pembeli', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelis');
    }
};
