<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sections')) {
            return;
        }

        $defaults = [
            ['name' => 'Creche', 'slug' => 'creche', 'description' => 'Creche section'],
            ['name' => 'Kindergarten', 'slug' => 'kindergarten', 'description' => 'Kindergarten section'],
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

        if (!Schema::hasTable('school_classes') || !Schema::hasColumn('school_classes', 'section_id')) {
            return;
        }

        $sectionMap = DB::table('sections')->pluck('id', 'slug')->all();
        $crecheId = $sectionMap['creche'] ?? null;
        $kindergartenId = $sectionMap['kindergarten'] ?? null;

        if (!$crecheId && !$kindergartenId) {
            return;
        }

        $classes = DB::table('school_classes')->get(['id', 'name']);
        foreach ($classes as $class) {
            $name = strtoupper((string) $class->name);
            if ($crecheId && Str::contains($name, ['CRECHE'])) {
                DB::table('school_classes')
                    ->where('id', $class->id)
                    ->update(['section_id' => $crecheId]);
                continue;
            }

            if ($kindergartenId && Str::contains($name, ['KINDERGARTEN', 'KINDER', 'KG '])) {
                DB::table('school_classes')
                    ->where('id', $class->id)
                    ->update(['section_id' => $kindergartenId]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sections')) {
            return;
        }

        $sectionMap = DB::table('sections')->pluck('id', 'slug')->all();
        $crecheId = $sectionMap['creche'] ?? null;
        $kindergartenId = $sectionMap['kindergarten'] ?? null;

        if (Schema::hasTable('school_classes') && Schema::hasColumn('school_classes', 'section_id')) {
            if ($crecheId) {
                DB::table('school_classes')
                    ->where('section_id', $crecheId)
                    ->update(['section_id' => null]);
            }
            if ($kindergartenId) {
                DB::table('school_classes')
                    ->where('section_id', $kindergartenId)
                    ->update(['section_id' => null]);
            }
        }

        DB::table('sections')->whereIn('slug', ['creche', 'kindergarten'])->delete();
    }
};
