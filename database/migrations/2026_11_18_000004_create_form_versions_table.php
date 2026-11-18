<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->jsonb('schema');
            $table->boolean('is_published')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'version_number']);
            $table->unique(['form_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_versions');
    }
};
