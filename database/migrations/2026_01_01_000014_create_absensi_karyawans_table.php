<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_karyawans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('id_cabang')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('id_user_input')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['Masuk', 'Setengah Hari', 'Tidak Masuk'])->default('Masuk');
            $table->decimal('nilai_hari', 3, 1)->default(1.0)->comment('1.0: Sehari/Masuk, 0.5: Setengah Hari, 0.0: Tidak Masuk');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['id_karyawan', 'tanggal']);
            $table->index(['id_cabang', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_karyawans');
    }
};
