<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian A3 (Fase 3, redesign Referensi + form submit anggota): kolom yang
 * sempat di-flag perlu migrasi terpisah di prompt master sebelumnya (belum
 * pernah dieksekusi -- dicek langsung, tidak ada migrasi lain yang
 * menyentuh tabel ini selain `create_learning_resources_table`).
 * `created_by` nullable karena referensi lama/dari admin lewat seeder tidak
 * punya pengaju (NULL = dari Admin, non-NULL = dari anggota tertentu,
 * dipakai langsung sebagai badge "Dari Admin"/"Dari Anggota" di UI).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->foreignUuid('created_by')->nullable()->after('module_id')->constrained('users')->nullOnDelete();
            $table->text('description')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('description');
        });
    }
};
