<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('form_id');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('queued');
            $table->jsonb('filters');
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->foreign(['workspace_id', 'form_id'])->references(['workspace_id', 'id'])->on('forms')->cascadeOnDelete();
            $table->index(['form_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_exports');
    }
};
