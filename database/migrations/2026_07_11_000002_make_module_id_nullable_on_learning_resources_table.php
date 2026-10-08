<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian A3 lanjutan (Fase 3): dukungan referensi "General" (tidak terikat
 * modul manapun) -- module_id sebelumnya NOT NULL sejak
 * create_learning_resources_table. Dikonfirmasi eksplisit ke Aye sebelum
 * dieksekusi (beda migrasi dari created_by/description sebelumnya).
 * cascadeOnDelete lama diganti nullOnDelete supaya konsisten dengan makna
 * baru "module_id null = General", bukan "wajib ada".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
        });

        Schema::table('learning_resources', function (Blueprint $table) {
            $table->foreignUuid('module_id')->nullable()->change();
            $table->foreign('module_id')->references('id')->on('modules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
        });

        Schema::table('learning_resources', function (Blueprint $table) {
            $table->foreignUuid('module_id')->nullable(false)->change();
            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
        });
    }
};
