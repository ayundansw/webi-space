<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8 (perbaikan pasca-Batch 5): DualModeService::revoke() now records
 * revocation as its OWN permanent DualModeRequest row (status='revoked')
 * rather than mutating users.dual_mode_status to a dead-end 'revoked'
 * value — see that method's docblock. Additive only, same pattern as the
 * notifications.type widenings before it.
 *
 * Deliberately does NOT touch users.dual_mode_status's own enum (still
 * has 'revoked' as a legal value from Fase 8 Batch 1) — that value is
 * simply never written anymore after this fix, left in place rather than
 * narrowed, per this fix's own "JANGAN ubah logic Batch 1-5 lain" scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dual_mode_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'revoked'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('dual_mode_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });
    }
};
