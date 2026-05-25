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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
        });

        $defaults = [
            ['name' => 'Creche', 'slug' => 'creche', 'description' => 'Creche section'],
            ['name' => 'Kindergarten', 'slug' => 'kindergarten', 'description' => 'Kindergarten section'],
            ['name' => 'Primary', 'slug' => 'primary', 'description' => 'Primary section'],
            ['name' => 'Junior Secondary', 'slug' => 'junior-secondary', 'description' => 'Junior secondary section'],
            ['name' => 'Senior Secondary', 'slug' => 'senior-secondary', 'description' => 'Senior secondary section'],
        ];

        foreach ($defaults as $section) {
            $exists = DB::table('sections')->where('slug', $section['slug'])->exists();
            if (!$exists) {
                DB::table('sections')->insert([
                    'name' => $section['name'],
                    'slug' => $section['slug'],
                    'description' => $section['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $sectionMap = DB::table('sections')->pluck('id', 'slug')->all();
        $crecheId = $sectionMap['creche'] ?? null;
        $kindergartenId = $sectionMap['kindergarten'] ?? null;
        $primaryId = $sectionMap['primary'] ?? null;
        $juniorId = $sectionMap['junior-secondary'] ?? $primaryId;
        $seniorId = $sectionMap['senior-secondary'] ?? $juniorId;

        $classes = DB::table('school_classes')->get(['id', 'name']);
        foreach ($classes as $class) {
            $name = strtoupper((string) $class->name);
            $sectionId = $primaryId;
            if ($crecheId && Str::contains($name, ['CRECHE'])) {
                $sectionId = $crecheId;
            } elseif ($kindergartenId && Str::contains($name, ['KINDERGARTEN', 'KINDER', 'KG '])) {
                $sectionId = $kindergartenId;
            } elseif (Str::contains($name, ['SSS', 'SENIOR', 'SS '])) {
                $sectionId = $seniorId;
            } elseif (Str::contains($name, ['JSS', 'JUNIOR'])) {
                $sectionId = $juniorId;
            }
            DB::table('school_classes')
                ->where('id', $class->id)
                ->update(['section_id' => $sectionId]);
        }

        $subjectSectionLookup = [];
        $subjectRows = DB::table('topics')
            ->join('school_classes', 'topics.school_class_id', '=', 'school_classes.id')
            ->select('topics.subject_id', 'school_classes.section_id')
            ->whereNotNull('school_classes.section_id')
            ->distinct()
            ->get();

        foreach ($subjectRows as $row) {
            $subjectId = (int) $row->subject_id;
            $sectionId = (int) $row->section_id;
            if (!isset($subjectSectionLookup[$subjectId])) {
                $subjectSectionLookup[$subjectId] = $sectionId;
                continue;
            }
            if ($subjectSectionLookup[$subjectId] !== $sectionId) {
                $subjectSectionLookup[$subjectId] = null;
            }
        }

        $subjects = DB::table('subjects')->get(['id']);
        foreach ($subjects as $subject) {
            $resolvedSection = $subjectSectionLookup[$subject->id] ?? null;
            $sectionId = $resolvedSection ?: $juniorId;
            DB::table('subjects')
                ->where('id', $subject->id)
                ->update(['section_id' => $sectionId]);
        }
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });

        Schema::dropIfExists('sections');
    }
};
