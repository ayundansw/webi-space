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
        Schema::create('checkpoint_completions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('checkpoint_id')->constrained('checkpoints')->cascadeOnDelete();
            $table->json('checklist_answers');
            $table->json('intermezo_answers');
            $table->text('form_tanggapan');
            $table->integer('points_awarded')->default(25);
            $table->timestamp('completed_at')->nullable();

            $table->unique(['user_id', 'checkpoint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkpoint_completions');
    }
};
