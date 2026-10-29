<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gaji_karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_gaji', 50)->unique();
            $table->foreignId('id_karyawan')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('id_cabang')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('id_user_admin')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('gaji_harian', 15, 2)->default(0);
            $table->decimal('total_hari_kerja', 5, 1)->default(0);
            $table->decimal('total_gaji_kotor', 15, 2)->default(0);
            $table->decimal('total_uang_makan', 15, 2)->default(0);
            $table->decimal('total_kasbon', 15, 2)->default(0);
            $table->decimal('bonus_tambahan', 15, 2)->default(0);
            $table->decimal('total_gaji_bersih', 15, 2)->default(0);
            $table->enum('status', ['Draft', 'Dibayarkan'])->default('Draft');
            $table->timestamp('tanggal_bayar')->nullable();
            $table->string('metode_bayar', 50)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['id_karyawan', 'bulan', 'tahun']);
            $table->index(['id_cabang', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gaji_karyawans');
    }
};
