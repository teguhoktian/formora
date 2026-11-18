<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table): void {
            $table->uuid('published_version_id')->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE forms ADD CONSTRAINT forms_published_version_form_fk FOREIGN KEY (id, published_version_id) REFERENCES form_versions (form_id, id) ON DELETE RESTRICT');
            DB::unprepared("CREATE FUNCTION prevent_published_form_version_change() RETURNS trigger AS $$ BEGIN IF OLD.is_published THEN RAISE EXCEPTION 'published form versions are immutable'; END IF; IF TG_OP = 'DELETE' THEN RETURN OLD; END IF; RETURN NEW; END; $$ LANGUAGE plpgsql");
            DB::unprepared('CREATE TRIGGER form_versions_immutable BEFORE UPDATE OR DELETE ON form_versions FOR EACH ROW EXECUTE FUNCTION prevent_published_form_version_change()');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER forms_published_version_matches_form_insert BEFORE INSERT ON forms WHEN NEW.published_version_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM form_versions WHERE form_id = NEW.id AND id = NEW.published_version_id) BEGIN SELECT RAISE(ABORT, 'published version must belong to form'); END");
            DB::unprepared("CREATE TRIGGER forms_published_version_matches_form_update BEFORE UPDATE OF id, published_version_id ON forms WHEN NEW.published_version_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM form_versions WHERE form_id = NEW.id AND id = NEW.published_version_id) BEGIN SELECT RAISE(ABORT, 'published version must belong to form'); END");
            DB::unprepared("CREATE TRIGGER form_versions_immutable_update BEFORE UPDATE ON form_versions WHEN OLD.is_published = 1 BEGIN SELECT RAISE(ABORT, 'published form versions are immutable'); END");
            DB::unprepared("CREATE TRIGGER form_versions_immutable_delete BEFORE DELETE ON form_versions WHEN OLD.is_published = 1 BEGIN SELECT RAISE(ABORT, 'published form versions are immutable'); END");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS form_versions_immutable ON form_versions');
            DB::statement('DROP FUNCTION IF EXISTS prevent_published_form_version_change()');
            DB::statement('ALTER TABLE forms DROP CONSTRAINT forms_published_version_form_fk');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS forms_published_version_matches_form_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS forms_published_version_matches_form_update');
            DB::unprepared('DROP TRIGGER IF EXISTS form_versions_immutable_update');
            DB::unprepared('DROP TRIGGER IF EXISTS form_versions_immutable_delete');
        }

        Schema::table('forms', function (Blueprint $table): void {
            $table->dropColumn('published_version_id');
        });
    }
};
