<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'paused', 'archived'])->default('draft');
            $table->timestamps();

            // Composite keys let child tables enforce tenant/form consistency.
            $table->unique(['workspace_id', 'id']);
            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
