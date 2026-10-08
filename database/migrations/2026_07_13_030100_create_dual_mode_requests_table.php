<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8 Batch 1: state machine pattern modeled after ProjectIdea (the
 * closest existing propose -> admin-decides analog, RECON_fase8_mode_ganda.md
 * §5) — single-step binary decision (pending -> approved/rejected), no
 * assignment/delegation step (unlike Praktik's assigned_reviewer_id;
 * §2.2.B has admin decide directly, no delegation concept for this).
 * `reviewed_at` doubles as ProjectIdea's implicit "already decided" guard
 * AND borrows Praktik Review's explicit reviewed_at-lock pattern to
 * prevent a double approve/reject race — checked by the Gate/service layer
 * in a later batch, not enforced here at the schema level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dual_mode_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dual_mode_requests');
    }
};
