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
        Schema::create('user_exploration_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->integer('current_level');
            $table->string('level_name');
            $table->integer('total_points')->default(0);
            $table->foreignUuid('current_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_exploration_progress');
    }
};
