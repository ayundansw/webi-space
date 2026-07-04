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
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('module_id')->constrained('modules')->cascadeOnDelete();
            $table->integer('order_number');
            $table->string('title');
            $table->text('content');
            $table->integer('estimated_minutes')->default(15);
            $table->enum('unit_type', ['concept', 'practice']);
            $table->integer('point_value');
            $table->enum('evaluation_type', ['quiz_multiple_choice', 'quiz_matching', 'quiz_ordering', 'essay', 'practice', 'none']);
            $table->foreignUuid('prerequisite_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
