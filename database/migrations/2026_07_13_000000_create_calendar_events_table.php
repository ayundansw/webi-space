<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 7 Batch 2b: this table only ever stores "Acara" (manual entries) —
 * docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md §Kalender: "Kegiatan"
 * (task deadlines + milestones) is deliberately NEVER duplicated in here,
 * always derived on-the-fly (see App\Services\Execution\CalendarService).
 *
 * `type` enum is a content sub-category of Acara only (design doc's own
 * examples: "kompetisi, meeting, kumpulan rutin, hal organisasi") — NOT
 * `deadline`/`milestone` (those are Kegiatan, never a row in this table)
 * and NOT `personal` (personal-vs-project scope is already the orthogonal
 * `project_id` nullability below; conflating it into `type` would allow a
 * meaningless "type=personal but project_id also set" state).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->enum('type', ['meeting', 'competition', 'other'])->default('other');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
