<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('form_id');
            $table->uuid('form_version_id');
            $table->enum('status', ['new', 'in_review', 'approved', 'rejected', 'completed'])->default('new');
            $table->jsonb('response_data');
            $table->timestamps();

            // These composite foreign keys prevent cross-workspace or wrong-version submissions.
            $table->foreign(['workspace_id', 'form_id'])
                ->references(['workspace_id', 'id'])
                ->on('forms')
                ->restrictOnDelete();
            $table->foreign(['form_id', 'form_version_id'])
                ->references(['form_id', 'id'])
                ->on('form_versions')
                ->restrictOnDelete();

            $table->index(['workspace_id', 'form_id', 'status']);
            $table->index(['form_id', 'form_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
