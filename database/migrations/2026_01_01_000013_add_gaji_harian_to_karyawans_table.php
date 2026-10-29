<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->decimal('gaji_harian', 15, 2)->default(0)->after('no_hp');
            $table->foreignId('id_cabang')->nullable()->after('gaji_harian')->constrained('cabangs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropForeign(['id_cabang']);
            $table->dropColumn(['gaji_harian', 'id_cabang']);
        });
    }
};
