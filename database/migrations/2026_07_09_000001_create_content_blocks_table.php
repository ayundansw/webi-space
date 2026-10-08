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
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('blockable');
            $table->enum('type', ['heading', 'text', 'image', 'callout', 'code', 'video', 'list', 'table', 'custom_html']);
            $table->json('content');
            $table->integer('order');
            $table->timestamps();

            $table->index(['blockable_type', 'blockable_id', 'order'], 'content_blocks_blockable_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
