<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('challenge_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('submission_type', ['link', 'text', 'file']);
            $table->text('content');
            $table->enum('status', ['pending', 'disetujui', 'perlu_revisi']);
            $table->text('feedback')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->integer('points_awarded')->nullable();
            $table->foreignUuid('assigned_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenge_submissions');
    }
};
