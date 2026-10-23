<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stoks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mutasi', 50)->unique();
            $table->foreignId('id_cabang_tujuan')->constrained('cabangs')->cascadeOnDelete();
            $table->foreignId('id_user_request')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_barang')->constrained('barangs')->cascadeOnDelete();
            $table->integer('jumlah_minta');
            $table->integer('jumlah_kirim')->nullable();
            $table->integer('jumlah_diterima')->nullable();
            $table->enum('sumber_tipe', ['Gudang Pusat', 'Cabang Lain'])->nullable();
            $table->foreignId('id_cabang_asal')->nullable()->constrained('cabangs')->nullOnDelete();
            $table->foreignId('id_user_dispatcher')->nullable()->constrained('users')->nullOnDelete()->comment('Admin Pusat yang approve & kirim');
            $table->foreignId('id_user_penerima')->nullable()->constrained('users')->nullOnDelete()->comment('Kasir Cabang yang konfirmasi terima fisik');
            $table->enum('status', ['Requested', 'Dispatched', 'Completed', 'Rejected'])->default('Requested');
            $table->text('catatan')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['id_cabang_tujuan', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stoks');
    }
};
