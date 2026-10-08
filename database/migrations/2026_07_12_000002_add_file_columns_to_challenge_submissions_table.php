<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Praktik 3 Bagian A: additive only. Not a reuse of the Task `Attachment`
 * table — that one is polymorphic-scoped to Task/Project and carries its own
 * RBAC (project membership), a different domain from Challenge Submission
 * (reviewer-assignment RBAC). `file_path` stores a relative path on the
 * private `attachments` disk (same disk Task attachments already use — see
 * config/filesystems.php — never the shared `local` disk).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_submissions', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('content');
            $table->string('file_path')->nullable()->after('file_name');
            $table->integer('file_size')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_submissions', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_path', 'file_size']);
        });
    }
};
