<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Praktik 3 Bagian B: admin-uploaded reference material for a Challenge
 * (briefs, starter assets, etc.) — separate from `challenge_submissions`'
 * file_* columns (Bagian A, member-uploaded answers) and from Task
 * `attachments` (different domain entirely). Files land on the same private
 * `attachments` disk as both of those, under their own subfolder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_attachments');
    }
};
