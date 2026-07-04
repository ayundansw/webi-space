<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widens attachments.file_url from string (varchar 255) to text. Needed for
 * the plain-text Attachment variant confirmed in task 2.4 (originally decided
 * in tahap 1.2 but missing from docs/arsitektur-database.md and the original
 * 2.0 migration) — plain-text notes can exceed 255 characters, unlike the
 * file/link variants that only ever store a short URL or file name there.
 * Explicitly confirmed with the user before altering existing schema.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->text('file_url')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('file_url')->change();
        });
    }
};
