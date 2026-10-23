<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_pusats', function (Blueprint $table) {
            $table->id();
            $table->decimal('saldo', 15, 2)->default(0)->comment('Saldo Kas Utama / Rekening Kantor Pusat');
            $table->timestamps();
        });

        Schema::create('operasional_pusats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kategori')->default('Operasional Kantor');
            $table->string('keterangan');
            $table->decimal('jumlah', 15, 2);
            $table->enum('status', ['Masuk', 'Keluar'])->comment('Masuk: Tambah Kas Pusat, Keluar: Potong Kas Pusat');
            $table->string('bukti_foto')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operasional_pusats');
        Schema::dropIfExists('kas_pusats');
    }
};
