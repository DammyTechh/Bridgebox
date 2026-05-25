<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('
            CREATE TABLE assessments_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                school_class_id INTEGER NULL REFERENCES school_classes(id) ON DELETE CASCADE,
                subject_id INTEGER NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
                topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
                type VARCHAR(20) NOT NULL,
                title VARCHAR NOT NULL,
                description TEXT NULL,
                time_limit_minutes INTEGER NULL,
                total_mark INTEGER NULL,
                pass_mark INTEGER NULL,
                retake_attempts INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )
        ');

        DB::statement('
            INSERT INTO assessments_new
                (id, school_class_id, subject_id, topic_id, type, title, description,
                 time_limit_minutes, total_mark, pass_mark, retake_attempts, created_at, updated_at)
            SELECT
                id, school_class_id, subject_id, topic_id, type, title, description,
                time_limit_minutes, total_mark, pass_mark, retake_attempts, created_at, updated_at
            FROM assessments
        ');

        DB::statement('DROP TABLE assessments');
        DB::statement('ALTER TABLE assessments_new RENAME TO assessments');
        DB::statement('CREATE INDEX assessments_type_topic_id_index ON assessments (type, topic_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('
            CREATE TABLE assessments_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                school_class_id INTEGER NOT NULL REFERENCES school_classes(id) ON DELETE CASCADE,
                subject_id INTEGER NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
                topic_id INTEGER NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
                type VARCHAR(20) NOT NULL,
                title VARCHAR NOT NULL,
                description TEXT NULL,
                time_limit_minutes INTEGER NULL,
                total_mark INTEGER NULL,
                pass_mark INTEGER NULL,
                retake_attempts INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )
        ');

        DB::statement('
            INSERT INTO assessments_new
                (id, school_class_id, subject_id, topic_id, type, title, description,
                 time_limit_minutes, total_mark, pass_mark, retake_attempts, created_at, updated_at)
            SELECT
                id, school_class_id, subject_id, topic_id, type, title, description,
                time_limit_minutes, total_mark, pass_mark, retake_attempts, created_at, updated_at
            FROM assessments
        ');

        DB::statement('DROP TABLE assessments');
        DB::statement('ALTER TABLE assessments_new RENAME TO assessments');
        DB::statement('CREATE INDEX assessments_type_topic_id_index ON assessments (type, topic_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
