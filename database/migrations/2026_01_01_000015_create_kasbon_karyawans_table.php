<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kasbon_karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kasbon', 50)->unique();
            $table->foreignId('id_karyawan')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('id_cabang')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('id_user_input')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->decimal('sisa_kasbon', 15, 2);
            $table->enum('status', ['Belum Lunas', 'Lunas'])->default('Belum Lunas');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['id_karyawan', 'status']);
            $table->index(['id_cabang', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_karyawans');
    }
};
