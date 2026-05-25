<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\SchoolClass;

class DemoGenericSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Digital Literacy',
                'code' => 'DL-101',
                'description' => 'Basic digital skills: using a computer, internet safety, and productivity tools.',
                'topics' => [
                    ['title' => 'Introduction to Computers', 'lessons' => ['Parts of a computer', 'Basic operation and safety']],
                    ['title' => 'Using the Internet', 'lessons' => ['Browsing basics', 'Search strategies', 'Online safety']],
                    ['title' => 'Productivity Tools', 'lessons' => ['Word processing basics', 'Spreadsheets 101', 'Presentations overview']],
                ],
            ],
            [
                'name' => 'Basic Computing',
                'code' => 'BC-101',
                'description' => 'Hands-on computing fundamentals for learners.',
                'topics' => [
                    ['title' => 'Files and Folders', 'lessons' => ['Creating folders', 'Organising files']],
                    ['title' => 'Operating Systems', 'lessons' => ['Introduction to OS', 'Basic settings']],
                ],
            ],
            [
                'name' => 'Numeracy Foundations',
                'code' => 'NF-101',
                'description' => 'Core numeracy skills for everyday use.',
                'topics' => [
                    ['title' => 'Number Sense', 'lessons' => ['Counting and place value', 'Basic operations']],
                    ['title' => 'Measurements', 'lessons' => ['Units and tools', 'Estimations']],
                ],
            ],
        ];

        $schoolClassId = $this->ensureDefaultSchoolClass();

        foreach ($courses as $c) {
            $subject = Subject::create([
                'name' => $c['name'],
                'code' => $c['code'],
                'description' => $c['description'],
                'section_id' => null,
            ]);

            foreach ($c['topics'] as $t) {
                $topic = Topic::create([
                    'school_class_id' => $schoolClassId,
                    'subject_id' => $subject->id,
                    'title' => $t['title'],
                    'description' => $t['title'] . ' overview',
                ]);

                foreach ($t['lessons'] as $lessonTitle) {
                    Lesson::create([
                        'topic_id' => $topic->id,
                        'title' => $lessonTitle,
                        'content' => '<p>This demo lesson covers: ' . htmlspecialchars($lessonTitle) . '.</p>',
                    ]);
                }
            }
        }

        // create sample assets folder placeholder
        $demoFolder = storage_path('app/demo_media');
        if (!is_dir($demoFolder)) {
            @mkdir($demoFolder, 0755, true);
        }
    }

    private function ensureDefaultSchoolClass(): int
    {
        $cls = SchoolClass::first();
        if ($cls) return $cls->id;

        $created = SchoolClass::create([
            'name' => 'Generic',
            'slug' => 'generic',
        ]);
        return $created->id;
    }
}
