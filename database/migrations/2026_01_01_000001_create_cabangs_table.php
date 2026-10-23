<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_cabang', 20)->unique()->nullable();
            $table->string('nama_cabang');
            $table->string('nama_tb')->comment('Nama Toko Bangunan untuk Header Struk/Faktur');
            $table->string('alamat')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->decimal('saldo', 15, 2)->default(0)->comment('Saldo Kas Laci Cabang Saat Ini');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabangs');
    }
};
