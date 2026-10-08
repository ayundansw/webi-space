<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Forum General Eksekusi (utang Fase 7 Batch 4): before this migration,
 * `forum_threads` had no way to tell an Eksplorasi "General" thread
 * (module_id/unit_id/project_id all null) apart from a future Eksekusi
 * "Forum General" thread (project_id null but NOT Eksplorasi content) —
 * both would look IDENTICAL from FK state alone. This adds an explicit,
 * NOT NULL `portal` column that resolves the ambiguity outright, rather
 * than a nullable "best guess" column, since the whole point is to never
 * again have a row whose portal is unknown.
 *
 * NOT NULL from a fresh ADD COLUMN is impossible with existing rows
 * present, so this is a genuine 3-step migration (add nullable -> backfill
 * every existing row -> tighten to NOT NULL), not a plain single-step
 * schema change — this table already has real (if few) rows in dev, and
 * could have real rows anywhere this app is deployed.
 *
 * Backfill rule (see this migration's own report for exact before/after
 * counts): `project_id IS NOT NULL` -> `execution` (every project-scoped
 * thread that exists before this migration is Forum Proyek from Fase 7
 * Batch 4, which only ever wrote project_id for Eksekusi threads).
 * `project_id IS NULL` -> `exploration` (every thread this old is either a
 * module/unit-scoped Eksplorasi thread, or an Eksplorasi "General" thread —
 * Forum General Eksekusi did not exist before this migration, so there is
 * no ambiguous case to resolve manually).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->enum('portal', ['exploration', 'execution'])->nullable()->after('project_id');
        });

        DB::table('forum_threads')->whereNotNull('project_id')->update(['portal' => 'execution']);
        DB::table('forum_threads')->whereNull('project_id')->update(['portal' => 'exploration']);

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->enum('portal', ['exploration', 'execution'])->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropColumn('portal');
        });
    }
};
