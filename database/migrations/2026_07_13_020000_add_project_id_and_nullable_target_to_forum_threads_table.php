<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 7 Batch 4 (docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md §5,
 * "Forum, dua kategori"): forum_threads gains `project_id` (nullable —
 * null means an Eksplorasi thread, exactly as before this migration;
 * set means an Eksekusi thread, either "Forum Proyek" for that specific
 * project or "Forum General" if also module_id/unit_id are null). Existing
 * rows are untouched (project_id defaults to null on ALTER, which is
 * exactly their correct meaning going forward).
 *
 * `target` (enum peer/pic) is widened to nullable — confirmed explicitly
 * with Aye before running this migration (AskUserQuestion) rather than
 * assumed: it's purely an Eksplorasi concept (peer vs PIC/pembina) with no
 * equivalent for Eksekusi threads, so a forced dummy value there would be
 * misleading data rather than a real signal. Existing Eksplorasi rows all
 * already have a real peer/pic value and are unaffected by widening the
 * constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->foreignUuid('project_id')->nullable()->after('unit_id')->constrained('projects')->nullOnDelete();
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->enum('target', ['peer', 'pic'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->enum('target', ['peer', 'pic'])->nullable(false)->change();
        });
    }
};
