<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8 Batch 1 (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2,
 * RECON_fase8_mode_ganda.md): schema only, no Gate/middleware logic here
 * (that's Batch 2+) — these columns aren't read by any access check yet.
 *
 * `dual_mode_status` is the CURRENT effective grant state, kept as its own
 * column (not derived by querying dual_mode_requests each time) so a
 * future Gate check is a single indexed column read, not a join/aggregate
 * over request history. `dual_mode_requests` (this migration's sibling)
 * is the audit trail of individual requests — a user can be rejected, then
 * request again, then approved, then later revoked; `dual_mode_status`
 * only reflects the latest outcome, the table remembers all of them.
 * Only relevant for exploration_member origin in practice (execution_member
 * origin gets read-only Eksplorasi access for free, no approval / no
 * status to track per §2.2.A) — left generic on the column itself since
 * enforcing "only exploration_member may be non-none" is a business rule
 * for the Gate layer (Batch 2+), not something a DB constraint should
 * encode.
 *
 * `active_mode` nullable, default null = "mode follows origin role" — see
 * this batch's report for why nullable-default was chosen over an
 * always-populated column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('dual_mode_status', ['none', 'pending', 'approved', 'revoked'])
                ->default('none')->after('membership_status');
            $table->enum('active_mode', ['exploration', 'execution'])
                ->nullable()->after('dual_mode_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dual_mode_status', 'active_mode']);
        });
    }
};
