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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->text('objective');
            $table->enum('project_type', ['internal', 'competition']);
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'archived']);
            $table->foreignUuid('originated_from_idea_id')->nullable()->constrained('project_ideas')->restrictOnDelete();
            $table->date('start_date');
            $table->date('target_end_date');
            $table->date('actual_end_date')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
