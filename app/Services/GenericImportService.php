<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\SchoolClass;

class GenericImportService
{
    /**
     * Integrate extracted import directory into subjects/topics/lessons.
     * Expects $dir to be an absolute path containing files and folders.
     */
    public function integrateImport(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        // Ensure an Imported Content subject exists
        $subject = Subject::firstOrCreate(
            ['name' => 'Imported Content'],
            ['code' => 'IMPORTED', 'description' => 'Content imported from external sources', 'section_id' => null]
        );

        $schoolClassId = $this->ensureDefaultSchoolClass();

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'import.zip') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->importFolder($path, $subject);
            } elseif (is_file($path)) {
                // treat files at root as part of a default topic
                $this->importFile($path, $subject, 'Imported Files');
            }
        }
    }

    protected function importFolder(string $path, Subject $subject): void
    {
        $topicTitle = basename($path);
        $topic = Topic::firstOrCreate(
            ['school_class_id' => $schoolClassId, 'subject_id' => $subject->id, 'title' => $topicTitle],
            ['description' => "Imported from {$topicTitle}"]
        );

        $files = scandir($path);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $filePath = $path . DIRECTORY_SEPARATOR . $f;
            if (is_file($filePath)) {
                $this->importFile($filePath, $subject, $topic->title);
            }
        }
    }

    protected function importFile(string $filePath, Subject $subject, string $topicTitle): void
    {
        $topic = Topic::firstOrCreate(
            ['school_class_id' => $schoolClassId, 'subject_id' => $subject->id, 'title' => $topicTitle],
            ['description' => "Imported topic {$topicTitle}"]
        );

        $basename = basename($filePath);
        $ext = pathinfo($basename, PATHINFO_EXTENSION);
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';

        // Move file into lessons storage
        $targetDir = storage_path('app/lessons');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $safeName = Str::slug(pathinfo($basename, PATHINFO_FILENAME));
        $newName = $safeName . '-' . time() . ($ext ? '.' . $ext : '');
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $newName;

        @copy($filePath, $targetPath);

        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'title' => pathinfo($basename, PATHINFO_FILENAME),
            'content' => null,
            'file_path' => 'lessons/' . $newName,
            'file_name' => $basename,
            'file_type' => $mime,
        ]);
    }

    private function ensureDefaultSchoolClass(): int
    {
        $cls = SchoolClass::first();
        if ($cls) return $cls->id;

        $created = SchoolClass::create(['name' => 'Generic']);
        return $created->id;
    }
}
