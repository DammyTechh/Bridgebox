<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentOption;
use App\Models\AssessmentQuestion;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAcademicSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['name' => 'Creche A', 'slug' => 'creche-a', 'description' => 'Creche class A'],
            ['name' => 'Creche B', 'slug' => 'creche-b', 'description' => 'Creche class B'],
            ['name' => 'KG 1', 'slug' => 'kg-1', 'description' => 'Kindergarten class 1'],
            ['name' => 'KG 2', 'slug' => 'kg-2', 'description' => 'Kindergarten class 2'],
            ['name' => 'JSS 1', 'slug' => 'jss-1', 'description' => 'Junior Secondary School 1'],
            ['name' => 'JSS 2', 'slug' => 'jss-2', 'description' => 'Junior Secondary School 2'],
            ['name' => 'SSS 1', 'slug' => 'sss-1', 'description' => 'Senior Secondary School 1'],
        ];

        $sections = [
            ['name' => 'Creche', 'slug' => 'creche', 'description' => 'Creche section'],
            ['name' => 'Kindergarten', 'slug' => 'kindergarten', 'description' => 'Kindergarten section'],
            ['name' => 'Primary', 'slug' => 'primary', 'description' => 'Primary section'],
            ['name' => 'Junior Secondary', 'slug' => 'junior-secondary', 'description' => 'Junior secondary section'],
            ['name' => 'Senior Secondary', 'slug' => 'senior-secondary', 'description' => 'Senior secondary section'],
        ];

        $subjects = [
            ['name' => 'Mathematics', 'description' => 'Numbers, algebra, and geometry fundamentals.'],
            ['name' => 'English Language', 'description' => 'Reading, writing, and comprehension skills.'],
            ['name' => 'Basic Science', 'description' => 'Scientific inquiry and practical experiments.'],
            ['name' => 'Social Studies', 'description' => 'Civic education, history, and community.'],
            ['name' => 'Agricultural Science', 'description' => 'Food production, soil, and farming basics.'],
        ];

        $departments = [
            ['name' => 'Science', 'description' => 'Science-focused learners and activities.'],
            ['name' => 'Arts', 'description' => 'Humanities and creative disciplines.'],
            ['name' => 'Commercial', 'description' => 'Business and commerce track.'],
        ];

        $topicMap = [
            'Mathematics' => [
                'Numbers and Place Value',
                'Fractions and Decimals',
                'Intro to Algebra',
                'Geometry Basics',
            ],
            'English Language' => [
                'Grammar and Parts of Speech',
                'Reading Comprehension',
                'Creative Writing',
                'Vocabulary Building',
            ],
            'Basic Science' => [
                'Living Things and Habitats',
                'Energy and Motion',
                'Matter and Materials',
                'Health and Hygiene',
            ],
            'Social Studies' => [
                'Community and Citizenship',
                'Nigerian History Overview',
                'Culture and Values',
                'Leadership and Responsibility',
            ],
            'Agricultural Science' => [
                'Soil Types and Care',
                'Crop Production Basics',
                'Farm Tools and Safety',
                'Livestock Management',
            ],
        ];

        $createdSections = collect($sections)->mapWithKeys(function ($section) {
            $record = Section::updateOrCreate(
                ['slug' => $section['slug']],
                [
                    'name' => $section['name'],
                    'description' => $section['description'],
                ]
            );

            return [$record->slug => $record];
        });

        $resolveSectionId = function (string $className) use ($createdSections): ?int {
            $name = strtoupper($className);
            if (str_contains($name, 'CRECHE')) {
                return $createdSections->get('creche')?->id;
            }
            if (str_contains($name, 'KINDERGARTEN') || str_contains($name, 'KINDER') || str_contains($name, 'KG ')) {
                return $createdSections->get('kindergarten')?->id;
            }
            if (str_contains($name, 'SSS') || str_contains($name, 'SENIOR')) {
                return $createdSections->get('senior-secondary')?->id;
            }
            if (str_contains($name, 'JSS') || str_contains($name, 'JUNIOR')) {
                return $createdSections->get('junior-secondary')?->id;
            }

            return $createdSections->get('primary')?->id;
        };

        $createdClasses = collect($classes)->map(function ($class) use ($resolveSectionId) {
            $sectionId = $resolveSectionId($class['name']);
            return SchoolClass::updateOrCreate(
                ['slug' => $class['slug']],
                [
                    'name' => $class['name'],
                    'description' => $class['description'],
                    'section_id' => $sectionId,
                ]
            );
        });

        $subjectsBySection = collect();
        foreach ($createdSections as $section) {
            $subjectsForSection = collect($subjects)->map(function ($subject) use ($section) {
                return Subject::updateOrCreate(
                    [
                        'code' => Str::slug($subject['name']),
                        'section_id' => $section->id,
                    ],
                    [
                        'name' => $subject['name'],
                        'description' => $subject['description'],
                        'section_id' => $section->id,
                    ]
                );
            });

            $subjectsBySection->put($section->id, $subjectsForSection);
        }

        $createdDepartments = collect($departments)->map(function ($department) {
            return Department::updateOrCreate(
                ['code' => Str::slug($department['name'])],
                ['name' => $department['name'], 'description' => $department['description']]
            );
        });

        $firstNames = [
            'Amina', 'Chinedu', 'Zainab', 'David', 'Hauwa', 'Ibrahim', 'Grace', 'Samuel', 'Kemi', 'Tunde',
            'Ngozi', 'Musa', 'Fatima', 'Daniel', 'Adaeze', 'Joseph', 'Maryam', 'Peter', 'Yusuf', 'Esther',
            'Ifeanyi', 'Ruth', 'Sulaiman', 'Christiana', 'Victor', 'Bola', 'Halima', 'Michael', 'Aisha', 'Emeka',
        ];
        $lastNames = [
            'Okoro', 'Bello', 'Adebayo', 'Okeke', 'Yusuf', 'Ibrahim', 'Lawal', 'Olawale', 'Eze', 'Nwosu',
            'Abdul', 'Ishola', 'Nwachukwu', 'Garba', 'Ogunleye', 'Umeh', 'Adeyemi', 'Onyeka', 'Sani', 'Balogun',
        ];
        $namePool = [];
        foreach ($firstNames as $first) {
            foreach ($lastNames as $last) {
                $namePool[] = "{$first} {$last}";
            }
        }
        $nameIndex = 0;
        $emailCounts = [];

        $primaryClass = $createdClasses->firstWhere('slug', 'jss-1') ?? $createdClasses->first();
        if ($primaryClass) {
            $teacher = User::where('email', 'chinedu.okoro@bridgebox.edu')->first();
            if ($teacher) {
                $teacher->update(['school_class_id' => $primaryClass->id]);
            }
        }

        foreach ($createdClasses as $classIndex => $class) {
            for ($i = 1; $i <= 8; $i++) {
                $name = $namePool[$nameIndex % count($namePool)];
                $nameIndex++;
                $emailLocal = Str::slug($name, '.');
                $count = ($emailCounts[$emailLocal] ?? 0) + 1;
                $emailCounts[$emailLocal] = $count;
                $email = $count === 1
                    ? "{$emailLocal}@bridgebox.edu"
                    : "{$emailLocal}{$count}@bridgebox.edu";
                $student = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'role' => User::ROLE_STUDENT,
                        'school_class_id' => $class->id,
                        'password' => Hash::make('BridgeBox@123'),
                    ]
                );

                if (!$student->studentProfile) {
                    $department = $createdDepartments->get($classIndex % max($createdDepartments->count(), 1));
                    $student->studentProfile()->create([
                        'class' => $class->name,
                        'department' => $department?->name,
                        'admission_id' => strtoupper($class->slug) . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    ]);
                }
            }
        }

        foreach ($createdClasses as $class) {
            $subjectsForSection = $subjectsBySection->get($class->section_id, collect());
            foreach ($subjectsForSection as $subject) {
                $subjectTopics = $topicMap[$subject->name] ?? ['Overview', 'Key Concepts'];
                foreach (array_slice($subjectTopics, 0, 2) as $topicTitle) {
                    $topic = Topic::updateOrCreate(
                        [
                            'school_class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'title' => $topicTitle,
                        ],
                        [
                            'description' => $topicTitle . ' for ' . $class->name . ' learners.',
                        ]
                    );

                    for ($lessonIndex = 1; $lessonIndex <= 2; $lessonIndex++) {
                        $lessonTitle = $topicTitle . ' - Lesson ' . $lessonIndex;
                        $lesson = Lesson::updateOrCreate(
                            [
                                'topic_id' => $topic->id,
                                'title' => $lessonTitle,
                            ],
                            [
                                'content' => "Lesson {$lessonIndex} introduces {$topicTitle}. Discuss key ideas, examples, and practice questions.",
                            ]
                        );

                        Assignment::updateOrCreate(
                            [
                                'lesson_id' => $lesson->id,
                                'title' => $lessonTitle . ' Assignment',
                            ],
                            [
                                'description' => 'Complete the worksheet and submit short answers.',
                                'due_at' => now()->addDays(7 + $lessonIndex),
                                'max_points' => 100,
                                'pass_mark' => 50,
                                'retake_attempts' => 1,
                                'allow_late' => $lessonIndex % 2 === 0,
                                'late_mark' => $lessonIndex % 2 === 0 ? 40 : null,
                                'late_due_at' => $lessonIndex % 2 === 0 ? now()->addDays(9 + $lessonIndex) : null,
                            ]
                        );
                    }
                }

                $topicForAssessment = Topic::where('school_class_id', $class->id)
                    ->where('subject_id', $subject->id)
                    ->orderBy('id')
                    ->first();

                if (!$topicForAssessment) {
                    continue;
                }

                foreach ([Assessment::TYPE_QUIZ, Assessment::TYPE_EXAM] as $type) {
                    $title = sprintf('%s %s - %s', $subject->name, ucfirst($type), $class->name);
                    $assessment = Assessment::updateOrCreate(
                        [
                            'school_class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'topic_id' => $topicForAssessment->id,
                            'type' => $type,
                            'title' => $title,
                        ],
                        [
                            'description' => 'Assessment covering recent lessons for ' . $subject->name . '.',
                            'time_limit_minutes' => $type === Assessment::TYPE_EXAM ? 45 : 20,
                            'total_mark' => 50,
                            'pass_mark' => 25,
                            'retake_attempts' => $type === Assessment::TYPE_EXAM ? 0 : 1,
                        ]
                    );

                    if ($assessment->questions()->count() === 0) {
                        for ($q = 1; $q <= 5; $q++) {
                            $question = AssessmentQuestion::create([
                                'assessment_id' => $assessment->id,
                                'prompt' => "Question {$q}: Choose the correct answer about {$subject->name}.",
                                'order' => $q,
                                'points' => 1,
                            ]);

                            $options = [
                                'A' => 'Option A',
                                'B' => 'Option B',
                                'C' => 'Option C',
                                'D' => 'Option D',
                            ];
                            $correctKey = array_keys($options)[$q % 4];

                            $order = 1;
                            foreach ($options as $key => $text) {
                                AssessmentOption::create([
                                    'assessment_question_id' => $question->id,
                                    'option_text' => $text,
                                    'is_correct' => $key === $correctKey,
                                    'order' => $order,
                                ]);
                                $order++;
                            }
                        }
                    }
                }
            }
        }
    }
}
