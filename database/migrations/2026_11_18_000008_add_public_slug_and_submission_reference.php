<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table): void {
            $table->string('slug', 180)->nullable();
        });

        DB::table('forms')->orderBy('id')->chunkById(200, function ($forms): void {
            foreach ($forms as $form) {
                $base = Str::slug($form->title) ?: 'form';
                $prefix = trim(Str::substr($base, 0, 140), '-');
                $suffix = Str::lower(str_replace('-', '', $form->id));
                DB::table('forms')->where('id', $form->id)->update(['slug' => $prefix.'-'.$suffix]);
            }
        }, 'id');

        Schema::table('forms', function (Blueprint $table): void {
            $table->unique('slug');
        });

        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->string('reference_number', 40)->nullable();
        });

        DB::table('form_submissions')->orderBy('id')->chunkById(200, function ($submissions): void {
            foreach ($submissions as $submission) {
                DB::table('form_submissions')->where('id', $submission->id)->update([
                    'reference_number' => 'FM-'.Str::upper(str_replace('-', '', $submission->id)),
                ]);
            }
        }, 'id');

        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->unique('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->dropUnique(['reference_number']);
            $table->dropColumn('reference_number');
        });

        Schema::table('forms', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
