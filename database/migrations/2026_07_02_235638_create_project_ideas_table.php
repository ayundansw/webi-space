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
        Schema::create('project_ideas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->text('purpose');
            $table->foreignUuid('proposed_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['draft', 'approved', 'rejected']);
            $table->text('rejection_reason')->nullable();
            $table->uuid('promoted_to_project_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_ideas');
    }
};
