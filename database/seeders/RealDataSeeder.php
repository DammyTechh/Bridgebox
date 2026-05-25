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

/**
 * Real-data seeder for BridgeBox.
 *
 * Creates:
 *  - 1 admin, 1 teacher, 10 students
 *  - 2 classes: JSS1A and JSS1B
 *  - 2 subjects per class: English Language and Mathematics
 *  - Real JSS1-level topics, lessons, assignments, quizzes and exams
 *
 * All passwords: BridgeBox@123
 */
class RealDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Section ────────────────────────────────────────────────────
        $jssSection = Section::updateOrCreate(
            ['slug' => 'junior-secondary'],
            ['name' => 'Junior Secondary', 'description' => 'JSS 1 – 3 classes']
        );

        // ── 2. Classes ────────────────────────────────────────────────────
        $classJSS1A = SchoolClass::updateOrCreate(
            ['slug' => 'jss1a'],
            ['name' => 'JSS1A', 'description' => 'Junior Secondary School 1, Stream A', 'section_id' => $jssSection->id]
        );
        $classJSS1B = SchoolClass::updateOrCreate(
            ['slug' => 'jss1b'],
            ['name' => 'JSS1B', 'description' => 'Junior Secondary School 1, Stream B', 'section_id' => $jssSection->id]
        );
        $classes = [$classJSS1A, $classJSS1B];

        // Department
        $artDepartment = Department::updateOrCreate(
            ['slug' => 'arts'],
            ['name' => 'Arts', 'description' => 'Arts subjects department']
        );

        $commercialDepartment = Department::updateOrCreate(
            ['slug' => 'commercial'],
            ['name' => 'Commercial', 'description' => 'Commercial subjects department']
        );

        $scienceDepartment = Department::updateOrCreate(
            ['slug' => 'science'],
            ['name' => 'Science', 'description' => 'Science subjects department']
        );

        // ── 3. Admin ──────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@bridgebox.edu'],
            [
                'name'           => 'Amina Yusuf',
                'role'           => 'admin',
                'password'       => Hash::make('BridgeBox@123'),
                'school_class_id'=> null,
            ]
        );

        // ── 4. Teacher ────────────────────────────────────────────────────
        $teacher = User::updateOrCreate(
            ['email' => 'mrs.adaeze.obi@bridgebox.edu'],
            [
                'name'           => 'Mrs Adaeze Obi',
                'role'           => 'teacher',
                'password'       => Hash::make('BridgeBox@123'),
                'school_class_id'=> $classJSS1A->id,
            ]
        );

        // ── 5. Students (10: 5 per class) ─────────────────────────────────
        $studentData = [
            // JSS1A
            ['name' => 'Emeka Okafor',     'email' => 'emeka.okafor@bridgebox.edu',     'class' => $classJSS1A, 'admission_id' => 'JSS1A-001', 'department' => $artDepartment],
            ['name' => 'Zainab Musa',      'email' => 'zainab.musa@bridgebox.edu',      'class' => $classJSS1A, 'admission_id' => 'JSS1A-002', 'department' => $commercialDepartment],
            ['name' => 'Tunde Adeleke',    'email' => 'tunde.adeleke@bridgebox.edu',    'class' => $classJSS1A, 'admission_id' => 'JSS1A-003', 'department' => $scienceDepartment],
            ['name' => 'Hauwa Ibrahim',    'email' => 'hauwa.ibrahim@bridgebox.edu',    'class' => $classJSS1A, 'admission_id' => 'JSS1A-004', 'department' => $artDepartment],
            ['name' => 'Chidi Nwosu',      'email' => 'chidi.nwosu@bridgebox.edu',      'class' => $classJSS1A, 'admission_id' => 'JSS1A-005', 'department' => $commercialDepartment],
            // JSS1B
            ['name' => 'Ngozi Eze',        'email' => 'ngozi.eze@bridgebox.edu',        'class' => $classJSS1B, 'admission_id' => 'JSS1B-001', 'department' => $artDepartment],
            ['name' => 'Yusuf Garba',      'email' => 'yusuf.garba@bridgebox.edu',      'class' => $classJSS1B, 'admission_id' => 'JSS1B-002', 'department' => $commercialDepartment],
            ['name' => 'Blessing Okonkwo', 'email' => 'blessing.okonkwo@bridgebox.edu', 'class' => $classJSS1B, 'admission_id' => 'JSS1B-003', 'department' => $scienceDepartment],
            ['name' => 'Musa Lawal',       'email' => 'musa.lawal@bridgebox.edu',       'class' => $classJSS1B, 'admission_id' => 'JSS1B-004', 'department' => $artDepartment],
            ['name' => 'Fatima Sani',      'email' => 'fatima.sani@bridgebox.edu',      'class' => $classJSS1B, 'admission_id' => 'JSS1B-005', 'department' => $commercialDepartment],
        ];

        foreach ($studentData as $row) {
            $student = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name'            => $row['name'],
                    'role'            => 'student',
                    'password'        => Hash::make('BridgeBox@123'),
                    'school_class_id' => $row['class']->id,
                ]
            );
            if (!$student->studentProfile) {
                $student->studentProfile()->create([
                    'class'        => $row['class']->name,
                    'admission_id' => $row['admission_id'],
                ]);
            }
        }

        // ── 6. Subjects (one set per class) ───────────────────────────────
        $subjectDefs = [
            [
                'name'        => 'English Language',
                'code'        => 'ENG',
                'description' => 'Reading Comprehension, Grammar, Composition and Oral English for JSS1 students.',
            ],
            [
                'name'        => 'Mathematics',
                'code'        => 'MTH',
                'description' => 'Number theory, basic algebra, geometry and statistics for JSS1 students.',
            ],
        ];

        // Build per-class subject map:  $subjects[$classId][$subjectName] = Subject
        $subjects = [];
        foreach ($classes as $class) {
            foreach ($subjectDefs as $def) {
                $subject = Subject::updateOrCreate(
                    ['code' => $def['code'], 'section_id' => $jssSection->id],
                    [
                        'name'        => $def['name'],
                        'description' => $def['description'],
                        'section_id'  => $jssSection->id,
                    ]
                );
                $subjects[$class->id][$def['name']] = $subject;
            }
        }

        // ── 7. Content definition ─────────────────────────────────────────
        // Structure: topic => [ lessons, assignments-per-lesson, quiz, exam ]
        // Expanded real JSS1 curriculum for both subjects.
        $curriculum = $this->getCurriculum();

        // ── 8. Seed topics, lessons, assignments, quizzes, exams ──────────
        foreach ($classes as $class) {
            foreach (['English Language', 'Mathematics'] as $subjectName) {
                $subject = $subjects[$class->id][$subjectName];
                $topicBlocks = $curriculum[$subjectName];

                foreach ($topicBlocks as $topicBlock) {
                    // Topic
                    $topic = Topic::updateOrCreate(
                        [
                            'school_class_id' => $class->id,
                            'subject_id'      => $subject->id,
                            'title'           => $topicBlock['title'],
                        ],
                        ['description' => $topicBlock['description']]
                    );

                    // Lessons + Assignments
                    foreach ($topicBlock['lessons'] as $lessonDef) {
                        $lesson = Lesson::updateOrCreate(
                            ['topic_id' => $topic->id, 'title' => $lessonDef['title']],
                            ['content' => $lessonDef['content']]
                        );

                        if (isset($lessonDef['assignment'])) {
                            $asgn = $lessonDef['assignment'];
                            Assignment::updateOrCreate(
                                ['lesson_id' => $lesson->id, 'title' => $asgn['title']],
                                [
                                    'description'     => $asgn['description'],
                                    'due_at'          => now()->addDays($asgn['due_days']),
                                    'max_points'      => $asgn['max_points'],
                                    'pass_mark'       => $asgn['pass_mark'],
                                    'retake_attempts' => 1,
                                    'allow_late'      => false,
                                ]
                            );
                        }
                    }

                    // Quiz
                    if (isset($topicBlock['quiz'])) {
                        $this->seedAssessment(
                            $class->id,
                            $subject->id,
                            $topic->id,
                            Assessment::TYPE_QUIZ,
                            $topicBlock['quiz']
                        );
                    }

                    // Exam
                    if (isset($topicBlock['exam'])) {
                        $this->seedAssessment(
                            $class->id,
                            $subject->id,
                            $topic->id,
                            Assessment::TYPE_EXAM,
                            $topicBlock['exam']
                        );
                    }
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    private function seedAssessment(
        int $classId,
        int $subjectId,
        int $topicId,
        string $type,
        array $def
    ): void {
        $assessment = Assessment::updateOrCreate(
            [
                'school_class_id' => $classId,
                'subject_id'      => $subjectId,
                'topic_id'        => $topicId,
                'type'            => $type,
                'title'           => $def['title'],
            ],
            [
                'description'       => $def['description'],
                'time_limit_minutes'=> $def['time_limit_minutes'],
                'total_mark'        => count($def['questions']),
                'pass_mark'         => (int) ceil(count($def['questions']) * 0.5),
                'retake_attempts'   => $type === Assessment::TYPE_EXAM ? 0 : 1,
            ]
        );

        if ($assessment->questions()->count() > 0) {
            return; // already seeded
        }

        foreach ($def['questions'] as $order => $qDef) {
            $question = AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'prompt'        => $qDef['prompt'],
                'order'         => $order + 1,
                'points'        => 1,
            ]);

            foreach ($qDef['options'] as $optOrder => $optDef) {
                AssessmentOption::create([
                    'assessment_question_id' => $question->id,
                    'option_text'            => $optDef['text'],
                    'is_correct'             => $optDef['correct'],
                    'order'                  => $optOrder + 1,
                ]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Curriculum data
    // ──────────────────────────────────────────────────────────────────────

    private function getCurriculum(): array
    {
        return [

            // ================================================================
            // ENGLISH LANGUAGE
            // ================================================================
            'English Language' => [

                // ── Topic 1: Parts of Speech ──────────────────────────────
                [
                    'title'       => 'Parts of Speech',
                    'description' => 'Understanding nouns, pronouns, verbs, adjectives, adverbs, prepositions, conjunctions and interjections.',
                    'lessons'     => [
                        [
                            'title'   => 'Nouns and Pronouns',
                            'content' => "A noun is a word that names a person, place, thing, or idea.\n\nTypes of nouns:\n• Proper nouns – specific names (Lagos, Emeka, Nigeria)\n• Common nouns – general names (city, boy, country)\n• Collective nouns – groups (flock, team, class)\n• Abstract nouns – ideas or feelings (joy, courage, love)\n\nA pronoun replaces a noun to avoid repetition.\nExamples: he, she, it, they, we, I, you.\n\nExample sentence: Emeka went to school. He carried his bag.\n('He' and 'his' replace 'Emeka'.)",
                            'assignment' => [
                                'title'       => 'Nouns and Pronouns Worksheet',
                                'description' => "1. Identify all the nouns in these sentences and classify them:\n   a) The teacher gave the students a difficult examination.\n   b) Courage is the key to success.\n   c) The flock of birds flew over Lagos.\n\n2. Replace the underlined nouns with correct pronouns:\n   a) Zainab and Hauwa went to the market. (Zainab and Hauwa) bought vegetables.\n   b) The dog wagged (the dog's) tail.\n\n3. Write 5 sentences of your own using at least one noun and one pronoun each.",
                                'due_days'    => 7,
                                'max_points'  => 20,
                                'pass_mark'   => 10,
                            ],
                        ],
                        [
                            'title'   => 'Verbs, Adjectives and Adverbs',
                            'content' => "Verbs are action or linking words: run, is, seem, have.\n\nTense matters:\n• Present: She reads every day.\n• Past: She read yesterday.\n• Future: She will read tomorrow.\n\nAdjectives describe nouns: a tall boy, the red car, three books.\n\nAdverbs describe verbs, adjectives or other adverbs: He ran quickly. She is very tall. He spoke quite clearly.\n\nTip: Many adverbs end in -ly (slowly, carefully) but not all (fast, hard, well).",
                            'assignment' => [
                                'title'       => 'Verbs, Adjectives and Adverbs Exercise',
                                'description' => "1. Underline the verb in each sentence and state its tense:\n   a) The students are playing football.\n   b) Musa solved the equation in two minutes.\n   c) We will travel to Abuja next week.\n\n2. Fill in the blank with a suitable adjective:\n   a) The _____ girl won the spelling competition.\n   b) He carried a _____ bag on his back.\n\n3. Rewrite these sentences by adding an appropriate adverb:\n   a) The teacher explained the topic.\n   b) Fatima completed her homework.",
                                'due_days'    => 7,
                                'max_points'  => 20,
                                'pass_mark'   => 10,
                            ],
                        ],
                    ],
                    'quiz' => [
                        'title'              => 'Parts of Speech – Quiz',
                        'description'        => 'A short quiz on nouns, pronouns, verbs, adjectives and adverbs.',
                        'time_limit_minutes' => 15,
                        'questions'          => [
                            [
                                'prompt'  => 'Which of the following is a proper noun?',
                                'options' => [
                                    ['text' => 'city',    'correct' => false],
                                    ['text' => 'Lagos',   'correct' => true],
                                    ['text' => 'teacher', 'correct' => false],
                                    ['text' => 'book',    'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'In the sentence "The brave soldier fought hard", what part of speech is "brave"?',
                                'options' => [
                                    ['text' => 'Noun',      'correct' => false],
                                    ['text' => 'Verb',      'correct' => false],
                                    ['text' => 'Adjective', 'correct' => true],
                                    ['text' => 'Adverb',    'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Choose the correct pronoun: "Emeka and I went to school. _____ carried our books."',
                                'options' => [
                                    ['text' => 'Him',  'correct' => false],
                                    ['text' => 'They', 'correct' => false],
                                    ['text' => 'We',   'correct' => true],
                                    ['text' => 'Us',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which word is an adverb in: "She sang beautifully"?',
                                'options' => [
                                    ['text' => 'She',         'correct' => false],
                                    ['text' => 'sang',        'correct' => false],
                                    ['text' => 'beautifully', 'correct' => true],
                                    ['text' => 'none',        'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What type of noun is "happiness"?',
                                'options' => [
                                    ['text' => 'Proper noun',     'correct' => false],
                                    ['text' => 'Collective noun', 'correct' => false],
                                    ['text' => 'Abstract noun',   'correct' => true],
                                    ['text' => 'Common noun',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Identify the verb in: "The children are playing in the yard."',
                                'options' => [
                                    ['text' => 'children', 'correct' => false],
                                    ['text' => 'yard',     'correct' => false],
                                    ['text' => 'playing',  'correct' => true],
                                    ['text' => 'The',      'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '"A herd of cattle" – "herd" is an example of which type of noun?',
                                'options' => [
                                    ['text' => 'Abstract',   'correct' => false],
                                    ['text' => 'Collective', 'correct' => true],
                                    ['text' => 'Proper',     'correct' => false],
                                    ['text' => 'Common',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which sentence uses the past tense correctly?',
                                'options' => [
                                    ['text' => 'She run to class every day.',        'correct' => false],
                                    ['text' => 'She runs to class every day.',       'correct' => false],
                                    ['text' => 'She ran to class yesterday.',        'correct' => true],
                                    ['text' => 'She will run to class yesterday.',   'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    'exam' => [
                        'title'              => 'Parts of Speech – Term Examination',
                        'description'        => 'End-of-term examination on all parts of speech covered in class.',
                        'time_limit_minutes' => 40,
                        'questions'          => [
                            [
                                'prompt'  => 'Which sentence contains a collective noun?',
                                'options' => [
                                    ['text' => 'The dog barked loudly.',              'correct' => false],
                                    ['text' => 'A pride of lions hunted at dawn.',    'correct' => true],
                                    ['text' => 'Freedom is priceless.',               'correct' => false],
                                    ['text' => 'Abuja is the capital of Nigeria.',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which word correctly fills the blank? "He spoke _____ to the audience."',
                                'options' => [
                                    ['text' => 'confident',    'correct' => false],
                                    ['text' => 'confidently',  'correct' => true],
                                    ['text' => 'confidente',   'correct' => false],
                                    ['text' => 'confidence',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'In "Nigeria is a beautiful country", what is the adjective?',
                                'options' => [
                                    ['text' => 'Nigeria',  'correct' => false],
                                    ['text' => 'country',  'correct' => false],
                                    ['text' => 'beautiful','correct' => true],
                                    ['text' => 'is',       'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is the plural of "child"?',
                                'options' => [
                                    ['text' => 'childs',    'correct' => false],
                                    ['text' => 'children',  'correct' => true],
                                    ['text' => 'childes',   'correct' => false],
                                    ['text' => 'childrens', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Identify the preposition in: "The book is on the table."',
                                'options' => [
                                    ['text' => 'book',  'correct' => false],
                                    ['text' => 'is',    'correct' => false],
                                    ['text' => 'on',    'correct' => true],
                                    ['text' => 'table', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which of the following is a conjunction?',
                                'options' => [
                                    ['text' => 'quickly', 'correct' => false],
                                    ['text' => 'and',     'correct' => true],
                                    ['text' => 'Lagos',   'correct' => false],
                                    ['text' => 'red',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '"Ouch! That hurt!" – What part of speech is "Ouch"?',
                                'options' => [
                                    ['text' => 'Adverb',       'correct' => false],
                                    ['text' => 'Interjection', 'correct' => true],
                                    ['text' => 'Conjunction',  'correct' => false],
                                    ['text' => 'Noun',         'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which pronoun replaces a singular female noun?',
                                'options' => [
                                    ['text' => 'he',   'correct' => false],
                                    ['text' => 'they', 'correct' => false],
                                    ['text' => 'she',  'correct' => true],
                                    ['text' => 'it',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is the future tense of "write"?',
                                'options' => [
                                    ['text' => 'wrote',      'correct' => false],
                                    ['text' => 'written',    'correct' => false],
                                    ['text' => 'will write', 'correct' => true],
                                    ['text' => 'writes',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'In "She runs every morning", the verb tense is:',
                                'options' => [
                                    ['text' => 'Past',    'correct' => false],
                                    ['text' => 'Present', 'correct' => true],
                                    ['text' => 'Future',  'correct' => false],
                                    ['text' => 'Perfect', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],

                // ── Topic 2: Reading Comprehension ────────────────────────
                [
                    'title'       => 'Reading Comprehension',
                    'description' => 'Developing skills to read passages carefully and answer questions on content, vocabulary and inference.',
                    'lessons'     => [
                        [
                            'title'   => 'Understanding a Passage',
                            'content' => "Comprehension is the ability to read and understand what a text is saying.\n\nSteps for effective comprehension:\n1. Read the passage carefully at least twice.\n2. Identify the topic – what is the passage mainly about?\n3. Note key words and phrases.\n4. Answer questions in your own words unless directed otherwise.\n5. Use evidence from the passage to support your answers.\n\nTypes of questions:\n• Factual – answers found directly in the passage.\n• Inferential – you must think beyond what is written.\n• Vocabulary – explain the meaning of a word as used in the passage.\n\nSample Passage:\n\"Bola woke up early and completed her chores before going to school. Her teacher praised her for always being punctual. Bola smiled and promised to keep up the habit.\"",
                            'assignment' => [
                                'title'       => 'Comprehension Passage Exercise',
                                'description' => "Read the passage below and answer the questions that follow:\n\n\"The rainy season in Nigeria usually begins around April and ends in October. During this period, farmers are very busy planting crops such as maize, cassava and yam. The rain brings life to the land but can also cause floods in low-lying areas. Students are advised to carry umbrellas or raincoats to school during this season.\"\n\n1. When does the rainy season begin in Nigeria?\n2. Mention two crops planted during the rainy season.\n3. Give one negative effect of the rainy season mentioned in the passage.\n4. Why should students carry umbrellas to school?\n5. What does the word 'low-lying' mean as used in the passage?",
                                'due_days'    => 5,
                                'max_points'  => 25,
                                'pass_mark'   => 13,
                            ],
                        ],
                        [
                            'title'   => 'Vocabulary in Context',
                            'content' => "Vocabulary in context means figuring out the meaning of an unfamiliar word from the sentences around it.\n\nStrategies:\n1. Look at the sentence where the word appears.\n2. Consider what word would make sense there.\n3. Look at surrounding sentences for clues.\n4. Think about any word parts you know (prefix, root, suffix).\n\nExample:\n\"The expedition was arduous. The hikers climbed steep mountains, crossed rivers and endured biting cold for seven days.\"\n→ 'Arduous' must mean very difficult or tiring, based on the description.\n\nSynonyms and Antonyms:\n• Synonym – a word with a similar meaning: big / large\n• Antonym – a word with the opposite meaning: hot / cold\n\nPractice expanding your vocabulary by reading widely – newspapers, textbooks and story books.",
                            'assignment' => [
                                'title'       => 'Vocabulary in Context Worksheet',
                                'description' => "1. Write the meaning of each underlined word as used in the sentence:\n   a) The generous man donated food to the (destitute) villagers.\n   b) The scientist made a remarkable (discovery) about plant growth.\n\n2. Find a synonym and an antonym for each word:\n   a) ancient\n   b) courage\n   c) simple\n\n3. Use each of these words correctly in a sentence of your own:\n   a) punctual\n   b) enormous\n   c) transparent",
                                'due_days'    => 7,
                                'max_points'  => 20,
                                'pass_mark'   => 10,
                            ],
                        ],
                    ],
                    'quiz' => [
                        'title'              => 'Reading Comprehension – Quiz',
                        'description'        => 'Quiz on comprehension skills and vocabulary in context.',
                        'time_limit_minutes' => 20,
                        'questions'          => [
                            [
                                'prompt'  => 'What is the first step when attempting a comprehension passage?',
                                'options' => [
                                    ['text' => 'Answer all questions immediately',          'correct' => false],
                                    ['text' => 'Read the questions before the passage',     'correct' => false],
                                    ['text' => 'Read the passage carefully at least twice', 'correct' => true],
                                    ['text' => 'Look up every unknown word in a dictionary','correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A question that requires you to go beyond what is directly stated is called:',
                                'options' => [
                                    ['text' => 'Factual question',     'correct' => false],
                                    ['text' => 'Inferential question', 'correct' => true],
                                    ['text' => 'Vocabulary question',  'correct' => false],
                                    ['text' => 'Summary question',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A synonym for "enormous" is:',
                                'options' => [
                                    ['text' => 'tiny',    'correct' => false],
                                    ['text' => 'quick',   'correct' => false],
                                    ['text' => 'huge',    'correct' => true],
                                    ['text' => 'careful', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'An antonym for "ancient" is:',
                                'options' => [
                                    ['text' => 'old',    'correct' => false],
                                    ['text' => 'modern', 'correct' => true],
                                    ['text' => 'large',  'correct' => false],
                                    ['text' => 'broken', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'In the passage "Bola woke up early and completed her chores before going to school", what can we infer about Bola?',
                                'options' => [
                                    ['text' => 'She was lazy',       'correct' => false],
                                    ['text' => 'She was responsible','correct' => true],
                                    ['text' => 'She hated school',   'correct' => false],
                                    ['text' => 'She had no chores',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Vocabulary answers in comprehension should be based on:',
                                'options' => [
                                    ['text' => 'The first definition in any dictionary',      'correct' => false],
                                    ['text' => 'How the word is used in the passage',         'correct' => true],
                                    ['text' => 'The most common meaning of the word',         'correct' => false],
                                    ['text' => 'What your classmate thinks the word means',   'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    'exam' => [
                        'title'              => 'Reading Comprehension – Term Examination',
                        'description'        => 'End-of-term examination on passage reading, inference and vocabulary.',
                        'time_limit_minutes' => 45,
                        'questions'          => [
                            [
                                'prompt'  => 'Which of the following best describes "reading comprehension"?',
                                'options' => [
                                    ['text' => 'Reading as fast as possible',                          'correct' => false],
                                    ['text' => 'Memorising every word of a passage',                   'correct' => false],
                                    ['text' => 'Understanding the meaning and details of a text read', 'correct' => true],
                                    ['text' => 'Copying a passage word for word',                      'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The rainy season in Nigeria ends around which month?',
                                'options' => [
                                    ['text' => 'June',     'correct' => false],
                                    ['text' => 'August',   'correct' => false],
                                    ['text' => 'October',  'correct' => true],
                                    ['text' => 'December', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '"Destitute" most likely means:',
                                'options' => [
                                    ['text' => 'wealthy',     'correct' => false],
                                    ['text' => 'very poor',   'correct' => true],
                                    ['text' => 'hardworking', 'correct' => false],
                                    ['text' => 'educated',    'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which word is a synonym of "punctual"?',
                                'options' => [
                                    ['text' => 'late',       'correct' => false],
                                    ['text' => 'absent',     'correct' => false],
                                    ['text' => 'on time',    'correct' => true],
                                    ['text' => 'careless',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A passage mainly discusses farming in Nigeria. The "topic" of the passage is:',
                                'options' => [
                                    ['text' => 'Nigeria\'s languages',    'correct' => false],
                                    ['text' => 'Farming in Nigeria',      'correct' => true],
                                    ['text' => 'Weather in Nigeria',      'correct' => false],
                                    ['text' => 'Schools in Nigeria',      'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which strategy helps you understand an unknown word in a passage?',
                                'options' => [
                                    ['text' => 'Skip the word and move on',                   'correct' => false],
                                    ['text' => 'Ask the teacher to read the passage for you', 'correct' => false],
                                    ['text' => 'Study the surrounding sentences for context clues', 'correct' => true],
                                    ['text' => 'Replace the word with any word you know',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'An antonym of "transparent" is:',
                                'options' => [
                                    ['text' => 'clear',  'correct' => false],
                                    ['text' => 'opaque', 'correct' => true],
                                    ['text' => 'bright', 'correct' => false],
                                    ['text' => 'light',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'You should answer comprehension questions:',
                                'options' => [
                                    ['text' => 'Using your imagination only',                              'correct' => false],
                                    ['text' => 'In your own words, supported by evidence from the passage','correct' => true],
                                    ['text' => 'By copying sentences directly at random',                  'correct' => false],
                                    ['text' => 'By answering all questions in one sentence',               'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],

                // ── Topic 3: Composition and Writing ─────────────────────
                [
                    'title'       => 'Composition and Writing',
                    'description' => 'Developing letter writing, narrative and descriptive essay skills.',
                    'lessons'     => [
                        [
                            'title'   => 'Letter Writing: Formal and Informal',
                            'content' => "There are two types of letters: formal and informal.\n\nFormal letters are written to people you do not know personally or in an official capacity (school principals, employers, government officials).\n\nFormat of a formal letter:\n1. Your address (top right)\n2. Date\n3. Recipient's name and address (left)\n4. Salutation: Dear Sir/Madam\n5. Subject heading (underlined)\n6. Body of the letter\n7. Formal closing: Yours faithfully / Yours sincerely\n8. Signature and name\n\nInformal letters are written to friends and family.\nThey use a friendlier tone: 'Dear Chidi,', closing with 'Your friend,' or 'Yours lovingly,'.\n\nKey differences:\n• Formal: official tone, no contractions, structured format.\n• Informal: casual tone, personal language, flexible format.",
                            'assignment' => [
                                'title'       => 'Letter Writing Assignment',
                                'description' => "Write ONE of the following letters:\n\nOption A (Formal): Write a letter to your school principal requesting permission to form a reading club. Your letter should include: your address, date, correct salutation, a clear purpose, at least three reasons why the club will benefit students, and a formal closing.\n\nOption B (Informal): Write a letter to your friend who recently moved to a new city. Tell them about life in your school, what you miss about them, and invite them to visit during the next school holiday.\n\nRequirements:\n• Minimum 2 paragraphs in the body\n• Correct format must be followed\n• Neat and legible handwriting (or typed neatly)",
                                'due_days'    => 10,
                                'max_points'  => 30,
                                'pass_mark'   => 15,
                            ],
                        ],
                        [
                            'title'   => 'Narrative and Descriptive Essays',
                            'content' => "A narrative essay tells a story, usually from the writer's own experience.\nIt has: a beginning (introduction), middle (events), and end (conclusion/lesson learnt).\n\nFeatures of a good narrative:\n• Clear sequence of events (use connectives: first, then, after that, finally)\n• Vivid details that help the reader picture the scene\n• A clear point of view (usually first person: I, we)\n• A satisfying conclusion\n\nA descriptive essay paints a picture in the reader's mind.\nIt uses sensory language (sight, sound, smell, taste, touch).\n\nExample (descriptive opening):\n\"The market was alive with colour. Traders shouted prices over the din of motorbikes. The smell of frying akara mingled with fresh tomatoes piled high in red pyramids.\"\n\nParagraph structure:\nEach paragraph should have:\n1. A topic sentence (main idea)\n2. Supporting details\n3. A closing or linking sentence",
                            'assignment' => [
                                'title'       => 'Essay Writing Assignment',
                                'description' => "Write a composition of between 250 and 350 words on ONE of the following topics:\n\n1. \"The day I was most proud of myself\" (Narrative)\n2. \"Describe your school environment\" (Descriptive)\n3. \"An unforgettable experience\" (Narrative)\n\nYour essay will be marked on:\n• Content and relevance (10 marks)\n• Organisation and paragraphing (10 marks)\n• Grammar and vocabulary (10 marks)",
                                'due_days'    => 10,
                                'max_points'  => 30,
                                'pass_mark'   => 15,
                            ],
                        ],
                    ],
                    'quiz' => [
                        'title'              => 'Composition and Writing – Quiz',
                        'description'        => 'Short quiz on letter writing and essay composition skills.',
                        'time_limit_minutes' => 15,
                        'questions'          => [
                            [
                                'prompt'  => 'A formal letter is addressed to:',
                                'options' => [
                                    ['text' => 'A close friend',                'correct' => false],
                                    ['text' => 'A family member',               'correct' => false],
                                    ['text' => 'An official or unknown person', 'correct' => true],
                                    ['text' => 'A classmate',                   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The correct closing for a formal letter when you know the recipient\'s name is:',
                                'options' => [
                                    ['text' => 'Yours faithfully', 'correct' => false],
                                    ['text' => 'Yours sincerely',  'correct' => true],
                                    ['text' => 'Your friend',      'correct' => false],
                                    ['text' => 'Best regards',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is the correct order for a formal letter?',
                                'options' => [
                                    ['text' => 'Body → Address → Date → Salutation', 'correct' => false],
                                    ['text' => 'Address → Date → Salutation → Body', 'correct' => true],
                                    ['text' => 'Date → Body → Address → Salutation', 'correct' => false],
                                    ['text' => 'Salutation → Address → Date → Body', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A narrative essay is primarily:',
                                'options' => [
                                    ['text' => 'A description of a place',      'correct' => false],
                                    ['text' => 'An argument about an issue',    'correct' => false],
                                    ['text' => 'A story based on experience',   'correct' => true],
                                    ['text' => 'A list of facts',               'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Sensory language in a descriptive essay refers to language that appeals to:',
                                'options' => [
                                    ['text' => 'Logic and reasoning',               'correct' => false],
                                    ['text' => 'Sight, sound, smell, taste, touch', 'correct' => true],
                                    ['text' => 'Dates and statistics',              'correct' => false],
                                    ['text' => 'Grammar rules only',                'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    'exam' => [
                        'title'              => 'Composition and Writing – Term Examination',
                        'description'        => 'End-of-term exam on formal/informal letters and essay writing.',
                        'time_limit_minutes' => 45,
                        'questions'          => [
                            [
                                'prompt'  => 'Which of these belongs in the heading of a formal letter?',
                                'options' => [
                                    ['text' => 'The writer\'s nickname',   'correct' => false],
                                    ['text' => 'The writer\'s address',    'correct' => true],
                                    ['text' => 'The essay topic',          'correct' => false],
                                    ['text' => 'The school motto',         'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'In an informal letter, the closing phrase can be:',
                                'options' => [
                                    ['text' => 'Yours faithfully',   'correct' => false],
                                    ['text' => 'To whom it concerns','correct' => false],
                                    ['text' => 'Your loving friend', 'correct' => true],
                                    ['text' => 'Respected Sir/Madam','correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A good essay paragraph should begin with:',
                                'options' => [
                                    ['text' => 'Any random detail',   'correct' => false],
                                    ['text' => 'A topic sentence',    'correct' => true],
                                    ['text' => 'A conclusion',        'correct' => false],
                                    ['text' => 'A vocabulary list',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which connective word is used to show the final event in a sequence?',
                                'options' => [
                                    ['text' => 'First',    'correct' => false],
                                    ['text' => 'Then',     'correct' => false],
                                    ['text' => 'Finally',  'correct' => true],
                                    ['text' => 'However',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Contractions such as "don\'t" and "we\'ll" should be AVOIDED in:',
                                'options' => [
                                    ['text' => 'Informal letters',   'correct' => false],
                                    ['text' => 'Narrative essays',   'correct' => false],
                                    ['text' => 'Formal letters',     'correct' => true],
                                    ['text' => 'Diary entries',      'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The subject heading in a formal letter is usually:',
                                'options' => [
                                    ['text' => 'Written in italics',     'correct' => false],
                                    ['text' => 'Written in brackets',    'correct' => false],
                                    ['text' => 'Underlined',             'correct' => true],
                                    ['text' => 'Written at the bottom',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'In a narrative essay, events should be presented:',
                                'options' => [
                                    ['text' => 'In random order',         'correct' => false],
                                    ['text' => 'In alphabetical order',   'correct' => false],
                                    ['text' => 'In a clear sequence',     'correct' => true],
                                    ['text' => 'From the end to the beginning','correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which of these is NOT a feature of descriptive writing?',
                                'options' => [
                                    ['text' => 'Vivid sensory details',  'correct' => false],
                                    ['text' => 'Paint a picture in words','correct' => false],
                                    ['text' => 'Persuade the reader to act','correct' => true],
                                    ['text' => 'Describe a person or place','correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The minimum requirement for a formal letter body is:',
                                'options' => [
                                    ['text' => 'One very long sentence',      'correct' => false],
                                    ['text' => 'No paragraphs needed',        'correct' => false],
                                    ['text' => 'A clear purpose with supporting details','correct' => true],
                                    ['text' => 'A drawing of the school',     'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which point of view is most commonly used in narrative essays?',
                                'options' => [
                                    ['text' => 'Second person (you/your)',   'correct' => false],
                                    ['text' => 'Third person (he/she/they)', 'correct' => false],
                                    ['text' => 'First person (I/we)',        'correct' => true],
                                    ['text' => 'No particular point of view','correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ================================================================
            // MATHEMATICS
            // ================================================================
            'Mathematics' => [

                // ── Topic 1: Whole Numbers and Number System ──────────────
                [
                    'title'       => 'Whole Numbers and the Number System',
                    'description' => 'Understanding counting numbers, place value, ordering and the four basic operations: addition, subtraction, multiplication and division.',
                    'lessons'     => [
                        [
                            'title'   => 'Place Value and Ordering Numbers',
                            'content' => "Every digit in a number has a place value.\n\nExample: In 4,572:\n• 4 is in the thousands place → 4 × 1000 = 4,000\n• 5 is in the hundreds place → 5 × 100 = 500\n• 7 is in the tens place → 7 × 10 = 70\n• 2 is in the units place → 2 × 1 = 2\nSo 4,572 = 4,000 + 500 + 70 + 2 (expanded form)\n\nComparison symbols:\n• > (greater than): 7,300 > 3,700\n• < (less than): 2,540 < 2,560\n• = (equal to)\n\nOrdering numbers:\n• Ascending order: from smallest to largest – 12, 45, 78, 102\n• Descending order: from largest to smallest – 102, 78, 45, 12\n\nRounding:\n• To the nearest 10: look at the units digit. If ≥ 5, round up; if < 5, round down.\n  e.g. 63 → 60, 67 → 70\n• To the nearest 100: look at the tens digit.\n  e.g. 450 → 500, 430 → 400",
                            'assignment' => [
                                'title'       => 'Place Value and Ordering Worksheet',
                                'description' => "1. Write the value of each underlined digit:\n   a) 3(6)52\n   b) 7,4(8)1\n   c) (9)3,206\n\n2. Write in expanded form:\n   a) 8,435\n   b) 12,709\n\n3. Arrange in ascending order: 3,041 | 3,140 | 3,014 | 3,401\n\n4. Round each number to the nearest 100:\n   a) 763\n   b) 1,349\n   c) 4,850\n\n5. Insert >, < or = between each pair:\n   a) 5,720 ____ 5,702\n   b) 8,008 ____ 8,080",
                                'due_days'    => 7,
                                'max_points'  => 25,
                                'pass_mark'   => 13,
                            ],
                        ],
                        [
                            'title'   => 'Addition, Subtraction, Multiplication and Division',
                            'content' => "Addition: combining numbers. 346 + 278 = 624 (column method)\nSubtraction: finding the difference. 502 − 187 = 315 (borrowing method)\n\nMultiplication:\n• Single digit: 7 × 8 = 56\n• Two digits: 43 × 25 (long multiplication)\n  43 × 25:\n  = 43 × 20 + 43 × 5\n  = 860 + 215\n  = 1,075\n\nDivision:\n• Short division: 168 ÷ 7 = 24\n• Long division: 504 ÷ 12 = 42\n  (Divide-Multiply-Subtract-Bring down)\n\nOrder of Operations – BODMAS:\nBrackets, Orders (powers), Division, Multiplication, Addition, Subtraction.\nAlways solve in this order.\nExample: 3 + 4 × 2 = 3 + 8 = 11 (not 14)\n\nWord problems:\n\"A school buys 8 boxes of chalk. Each box contains 144 sticks. How many sticks in total? 8 × 144 = 1,152 sticks.\"",
                            'assignment' => [
                                'title'       => 'Four Operations and BODMAS Worksheet',
                                'description' => "1. Work out:\n   a) 4,386 + 2,957\n   b) 7,000 − 3,482\n   c) 67 × 43\n   d) 936 ÷ 12\n\n2. Apply BODMAS to find the answer:\n   a) 5 + 3 × 4 − 2\n   b) (6 + 4) × 3 ÷ 5\n   c) 20 − (8 ÷ 4) + 7\n\n3. Word problems:\n   a) A farmer harvests 264 yams from each of his 15 plots. How many yams does he harvest in total?\n   b) A teacher shares 480 exercise books equally among 24 students. How many does each student receive?",
                                'due_days'    => 7,
                                'max_points'  => 30,
                                'pass_mark'   => 15,
                            ],
                        ],
                    ],
                    'quiz' => [
                        'title'              => 'Whole Numbers – Quiz',
                        'description'        => 'Quiz on place value, ordering, rounding and the four operations.',
                        'time_limit_minutes' => 20,
                        'questions'          => [
                            [
                                'prompt'  => 'What is the place value of 7 in 47,382?',
                                'options' => [
                                    ['text' => 'Hundreds',   'correct' => false],
                                    ['text' => 'Thousands',  'correct' => true],
                                    ['text' => 'Tens',       'correct' => false],
                                    ['text' => 'Units',      'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Round 2,748 to the nearest hundred.',
                                'options' => [
                                    ['text' => '2,700', 'correct' => true],
                                    ['text' => '2,750', 'correct' => false],
                                    ['text' => '2,800', 'correct' => false],
                                    ['text' => '3,000', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which number is LARGEST?',
                                'options' => [
                                    ['text' => '4,981', 'correct' => false],
                                    ['text' => '4,918', 'correct' => false],
                                    ['text' => '4,991', 'correct' => true],
                                    ['text' => '4,909', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is 5 + 3 × 4? (Apply BODMAS)',
                                'options' => [
                                    ['text' => '32', 'correct' => false],
                                    ['text' => '17', 'correct' => true],
                                    ['text' => '20', 'correct' => false],
                                    ['text' => '27', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is 504 ÷ 12?',
                                'options' => [
                                    ['text' => '40', 'correct' => false],
                                    ['text' => '42', 'correct' => true],
                                    ['text' => '44', 'correct' => false],
                                    ['text' => '48', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The expanded form of 6,309 is:',
                                'options' => [
                                    ['text' => '6,000 + 30 + 9',        'correct' => false],
                                    ['text' => '6,000 + 300 + 9',       'correct' => true],
                                    ['text' => '600 + 300 + 9',         'correct' => false],
                                    ['text' => '6,000 + 300 + 90',      'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is 67 × 43?',
                                'options' => [
                                    ['text' => '2,781', 'correct' => false],
                                    ['text' => '2,871', 'correct' => true],
                                    ['text' => '2,681', 'correct' => false],
                                    ['text' => '2,981', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Arrange in ASCENDING order: 3,401 | 3,041 | 3,140 | 3,014',
                                'options' => [
                                    ['text' => '3,401, 3,140, 3,041, 3,014', 'correct' => false],
                                    ['text' => '3,014, 3,041, 3,140, 3,401', 'correct' => true],
                                    ['text' => '3,041, 3,014, 3,140, 3,401', 'correct' => false],
                                    ['text' => '3,140, 3,041, 3,014, 3,401', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    'exam' => [
                        'title'              => 'Whole Numbers – Term Examination',
                        'description'        => 'End-of-term exam on number systems, operations and problem solving.',
                        'time_limit_minutes' => 45,
                        'questions'          => [
                            [
                                'prompt'  => 'What is the value of 8 in 83,542?',
                                'options' => [
                                    ['text' => '8',        'correct' => false],
                                    ['text' => '800',      'correct' => false],
                                    ['text' => '8,000',    'correct' => false],
                                    ['text' => '80,000',   'correct' => true],
                                ],
                            ],
                            [
                                'prompt'  => 'What is 7,000 − 3,482?',
                                'options' => [
                                    ['text' => '3,518', 'correct' => true],
                                    ['text' => '3,582', 'correct' => false],
                                    ['text' => '4,518', 'correct' => false],
                                    ['text' => '3,418', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A farmer harvests 264 yams from each of 15 plots. How many yams in total?',
                                'options' => [
                                    ['text' => '3,860', 'correct' => false],
                                    ['text' => '3,960', 'correct' => true],
                                    ['text' => '4,060', 'correct' => false],
                                    ['text' => '3,660', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Apply BODMAS: (6 + 4) × 3 ÷ 5',
                                'options' => [
                                    ['text' => '6',  'correct' => true],
                                    ['text' => '8',  'correct' => false],
                                    ['text' => '10', 'correct' => false],
                                    ['text' => '30', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Round 4,850 to the nearest hundred.',
                                'options' => [
                                    ['text' => '4,800', 'correct' => false],
                                    ['text' => '4,900', 'correct' => true],
                                    ['text' => '5,000', 'correct' => false],
                                    ['text' => '4,850', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '480 exercise books shared among 24 students gives each student:',
                                'options' => [
                                    ['text' => '18', 'correct' => false],
                                    ['text' => '20', 'correct' => true],
                                    ['text' => '22', 'correct' => false],
                                    ['text' => '24', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which of these shows DESCENDING order?',
                                'options' => [
                                    ['text' => '12, 45, 78, 102',  'correct' => false],
                                    ['text' => '102, 78, 45, 12',  'correct' => true],
                                    ['text' => '45, 12, 102, 78',  'correct' => false],
                                    ['text' => '78, 12, 45, 102',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '4,386 + 2,957 = ?',
                                'options' => [
                                    ['text' => '7,243', 'correct' => false],
                                    ['text' => '7,343', 'correct' => true],
                                    ['text' => '7,443', 'correct' => false],
                                    ['text' => '7,143', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Apply BODMAS: 20 − (8 ÷ 4) + 7',
                                'options' => [
                                    ['text' => '25', 'correct' => true],
                                    ['text' => '27', 'correct' => false],
                                    ['text' => '23', 'correct' => false],
                                    ['text' => '20', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The symbol < means:',
                                'options' => [
                                    ['text' => 'greater than', 'correct' => false],
                                    ['text' => 'equal to',     'correct' => false],
                                    ['text' => 'less than',    'correct' => true],
                                    ['text' => 'not equal to', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],

                // ── Topic 2: Fractions and Decimals ───────────────────────
                [
                    'title'       => 'Fractions and Decimals',
                    'description' => 'Understanding proper and improper fractions, equivalent fractions, operations on fractions, and converting between fractions and decimals.',
                    'lessons'     => [
                        [
                            'title'   => 'Types of Fractions and Equivalent Fractions',
                            'content' => "A fraction represents part of a whole.\nFraction = Numerator / Denominator\n\nTypes of fractions:\n• Proper fraction: numerator < denominator. e.g. 3/4, 2/5\n• Improper fraction: numerator ≥ denominator. e.g. 7/3, 5/5\n• Mixed number: a whole + a proper fraction. e.g. 2¹/₃\n\nConverting:\n• Improper to mixed: divide. 7/3 = 2 remainder 1 = 2¹/₃\n• Mixed to improper: multiply whole × denominator + numerator. 2¹/₃ = (2×3+1)/3 = 7/3\n\nEquivalent fractions have the same value:\n1/2 = 2/4 = 3/6 = 4/8 (multiply or divide numerator and denominator by the same number)\n\nSimplifying: divide both numerator and denominator by their HCF.\n12/18: HCF = 6 → 12÷6 / 18÷6 = 2/3\n\nComparing fractions: convert to a common denominator, then compare numerators.",
                            'assignment' => [
                                'title'       => 'Fractions Worksheet Part 1',
                                'description' => "1. Classify each fraction as proper, improper or mixed:\n   a) 5/9  b) 11/4  c) 3²/₅  d) 7/7\n\n2. Convert to mixed numbers:\n   a) 13/4  b) 22/7  c) 17/5\n\n3. Convert to improper fractions:\n   a) 3²/₃  b) 5¹/₄  c) 2⁵/₆\n\n4. Write two equivalent fractions for each:\n   a) 1/3  b) 4/5\n\n5. Simplify to lowest terms:\n   a) 16/24  b) 30/45  c) 18/27",
                                'due_days'    => 7,
                                'max_points'  => 25,
                                'pass_mark'   => 13,
                            ],
                        ],
                        [
                            'title'   => 'Operations on Fractions and Decimals',
                            'content' => "Adding/Subtracting fractions:\n• Same denominator: add/subtract numerators. 3/8 + 2/8 = 5/8\n• Different denominators: find the LCM (Lowest Common Multiple) first.\n  1/3 + 1/4: LCM of 3 and 4 = 12 → 4/12 + 3/12 = 7/12\n\nMultiplying fractions: multiply numerators, then denominators.\n  2/3 × 3/4 = 6/12 = 1/2\n\nDividing fractions: flip the second fraction (reciprocal) and multiply.\n  2/3 ÷ 4/5 = 2/3 × 5/4 = 10/12 = 5/6\n\nDecimals:\n• Tenths: 0.1 = 1/10\n• Hundredths: 0.01 = 1/100\n\nConverting fraction → decimal: divide numerator by denominator.\n  3/4 = 3 ÷ 4 = 0.75\n\nConverting decimal → fraction: write over place-value denominator, simplify.\n  0.6 = 6/10 = 3/5\n\nAdding/subtracting decimals: line up the decimal points.\n  12.35 + 4.6 = 16.95\n\nMultiplying decimals: multiply as whole numbers, then count decimal places.\n  0.4 × 0.3 = 0.12",
                            'assignment' => [
                                'title'       => 'Fractions and Decimals Operations',
                                'description' => "1. Calculate:\n   a) 3/7 + 2/7\n   b) 5/6 − 1/4\n   c) 3/4 × 8/9\n   d) 5/6 ÷ 2/3\n\n2. Convert to decimals:\n   a) 3/5  b) 7/8  c) 1/4\n\n3. Convert to fractions in lowest terms:\n   a) 0.4  b) 0.75  c) 0.125\n\n4. Calculate:\n   a) 7.35 + 4.6\n   b) 12.05 − 3.7\n   c) 0.6 × 0.5\n\n5. Word problem:\n   Emeka used ³/₅ of a bag of rice on Monday and ¹/₄ of the same bag on Tuesday.\n   What fraction of the bag did he use altogether?\n   How much is left?",
                                'due_days'    => 7,
                                'max_points'  => 30,
                                'pass_mark'   => 15,
                            ],
                        ],
                    ],
                    'quiz' => [
                        'title'              => 'Fractions and Decimals – Quiz',
                        'description'        => 'Quiz on fraction types, equivalent fractions, operations and decimals.',
                        'time_limit_minutes' => 20,
                        'questions'          => [
                            [
                                'prompt'  => 'Which of the following is an improper fraction?',
                                'options' => [
                                    ['text' => '3/7',   'correct' => false],
                                    ['text' => '5/5',   'correct' => true],
                                    ['text' => '2/9',   'correct' => false],
                                    ['text' => '1/100', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Convert 13/4 to a mixed number.',
                                'options' => [
                                    ['text' => '3¹/₄', 'correct' => true],
                                    ['text' => '2³/₄', 'correct' => false],
                                    ['text' => '4¹/₃', 'correct' => false],
                                    ['text' => '3³/₄', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is 1/3 + 1/4?',
                                'options' => [
                                    ['text' => '2/7',  'correct' => false],
                                    ['text' => '7/12', 'correct' => true],
                                    ['text' => '1/12', 'correct' => false],
                                    ['text' => '2/12', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '3/4 as a decimal is:',
                                'options' => [
                                    ['text' => '0.34', 'correct' => false],
                                    ['text' => '0.7',  'correct' => false],
                                    ['text' => '0.75', 'correct' => true],
                                    ['text' => '0.3',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Which of these is equivalent to 2/3?',
                                'options' => [
                                    ['text' => '3/4',  'correct' => false],
                                    ['text' => '4/6',  'correct' => true],
                                    ['text' => '2/4',  'correct' => false],
                                    ['text' => '6/12', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Simplify 16/24.',
                                'options' => [
                                    ['text' => '4/8', 'correct' => false],
                                    ['text' => '2/3', 'correct' => true],
                                    ['text' => '8/12','correct' => false],
                                    ['text' => '3/4', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is 2/3 ÷ 4/5?',
                                'options' => [
                                    ['text' => '8/15', 'correct' => false],
                                    ['text' => '5/6',  'correct' => true],
                                    ['text' => '2/5',  'correct' => false],
                                    ['text' => '6/5',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '0.125 as a fraction in lowest terms is:',
                                'options' => [
                                    ['text' => '1/4', 'correct' => false],
                                    ['text' => '1/8', 'correct' => true],
                                    ['text' => '1/5', 'correct' => false],
                                    ['text' => '5/8', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    'exam' => [
                        'title'              => 'Fractions and Decimals – Term Examination',
                        'description'        => 'End-of-term examination on fractions, decimals and their operations.',
                        'time_limit_minutes' => 45,
                        'questions'          => [
                            [
                                'prompt'  => 'Which is a proper fraction?',
                                'options' => [
                                    ['text' => '9/4',  'correct' => false],
                                    ['text' => '7/7',  'correct' => false],
                                    ['text' => '5/8',  'correct' => true],
                                    ['text' => '10/3', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Convert 3²/₃ to an improper fraction.',
                                'options' => [
                                    ['text' => '9/3',  'correct' => false],
                                    ['text' => '11/3', 'correct' => true],
                                    ['text' => '8/3',  'correct' => false],
                                    ['text' => '7/3',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '5/6 − 1/4 = ?',
                                'options' => [
                                    ['text' => '4/2',  'correct' => false],
                                    ['text' => '7/12', 'correct' => true],
                                    ['text' => '5/12', 'correct' => false],
                                    ['text' => '9/12', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '3/4 × 8/9 in lowest terms = ?',
                                'options' => [
                                    ['text' => '24/36', 'correct' => false],
                                    ['text' => '2/3',   'correct' => true],
                                    ['text' => '1/3',   'correct' => false],
                                    ['text' => '3/8',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'If Emeka used ³/₅ and ¹/₄ of a bag of rice, what fraction did he use in total?',
                                'options' => [
                                    ['text' => '4/9',   'correct' => false],
                                    ['text' => '17/20', 'correct' => true],
                                    ['text' => '7/20',  'correct' => false],
                                    ['text' => '3/9',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '7.35 + 4.6 = ?',
                                'options' => [
                                    ['text' => '11.85', 'correct' => false],
                                    ['text' => '11.95', 'correct' => true],
                                    ['text' => '12.05', 'correct' => false],
                                    ['text' => '11.75', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '12.05 − 3.7 = ?',
                                'options' => [
                                    ['text' => '9.25', 'correct' => false],
                                    ['text' => '8.35', 'correct' => true],
                                    ['text' => '8.65', 'correct' => false],
                                    ['text' => '9.35', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'What is 0.6 × 0.5?',
                                'options' => [
                                    ['text' => '0.03', 'correct' => false],
                                    ['text' => '3.0',  'correct' => false],
                                    ['text' => '0.30', 'correct' => true],
                                    ['text' => '0.11', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => '0.75 as a fraction is:',
                                'options' => [
                                    ['text' => '7/5',  'correct' => false],
                                    ['text' => '3/4',  'correct' => true],
                                    ['text' => '7/10', 'correct' => false],
                                    ['text' => '5/4',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The HCF of 30 and 45 is:',
                                'options' => [
                                    ['text' => '5',  'correct' => false],
                                    ['text' => '15', 'correct' => true],
                                    ['text' => '9',  'correct' => false],
                                    ['text' => '6',  'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],

                // ── Topic 3: Basic Geometry ───────────────────────────────
                [
                    'title'       => 'Basic Geometry: Lines, Angles and Shapes',
                    'description' => 'Understanding types of lines, measuring and classifying angles, and properties of 2D shapes.',
                    'lessons'     => [
                        [
                            'title'   => 'Lines and Angles',
                            'content' => "Types of lines:\n• Straight line: extends infinitely in both directions\n• Line segment: has two endpoints. e.g. AB\n• Ray: starts at one point and extends infinitely in one direction\n• Parallel lines: always the same distance apart, never meeting. Symbol ∥\n• Perpendicular lines: meet at exactly 90°. Symbol ⊥\n• Intersecting lines: cross at a point\n\nAngles: formed when two rays share a common endpoint (vertex).\n\nTypes of angles:\n• Acute angle: less than 90°\n• Right angle: exactly 90° (shown with a square symbol)\n• Obtuse angle: between 90° and 180°\n• Straight angle: exactly 180°\n• Reflex angle: between 180° and 360°\n\nMeasuring angles:\nUse a protractor. Place the midpoint on the vertex, align one ray with the base line, and read the scale.\n\nAngles on a straight line add up to 180°.\nAngles around a point add up to 360°.",
                            'assignment' => [
                                'title'       => 'Lines and Angles Worksheet',
                                'description' => "1. Classify each angle as acute, right, obtuse, straight or reflex:\n   a) 45°   b) 90°   c) 135°   d) 180°   e) 270°\n\n2. Two angles on a straight line are x and 3x. Find x and state the size of each angle.\n\n3. The angles around a point are 90°, 120°, and y. Find y.\n\n4. Draw and label:\n   a) A pair of parallel lines with a transversal\n   b) A right angle\n   c) An obtuse angle of 120°\n\n5. True or False:\n   a) Perpendicular lines form a reflex angle.\n   b) An acute angle is always less than 90°.",
                                'due_days'    => 7,
                                'max_points'  => 25,
                                'pass_mark'   => 13,
                            ],
                        ],
                        [
                            'title'   => 'Properties of 2D Shapes',
                            'content' => "Two-dimensional (2D) shapes are flat and have length and width.\n\nTriangles – 3 sides, angles sum to 180°:\n• Equilateral: 3 equal sides, 3 equal angles of 60°\n• Isosceles: 2 equal sides, 2 equal base angles\n• Scalene: no equal sides or angles\n• Right-angled: one angle is 90°\n\nQuadrilaterals – 4 sides, angles sum to 360°:\n• Square: 4 equal sides, all 90° angles, 2 pairs of parallel sides\n• Rectangle: opposite sides equal, all 90° angles, 2 pairs of parallel sides\n• Parallelogram: opposite sides equal and parallel, opposite angles equal\n• Rhombus: 4 equal sides, opposite angles equal\n• Trapezium: exactly one pair of parallel sides\n\nCircle:\n• Radius (r): centre to edge\n• Diameter (d): edge to edge through centre; d = 2r\n• Circumference: distance around the circle; C = 2πr or πd\n• Area of circle: A = πr²\n\nPerimeter: total distance around a shape.\n• Rectangle: P = 2(l + w)\n• Square: P = 4s\n\nArea: measurement of the surface inside a shape.\n• Rectangle: A = l × w\n• Triangle: A = ½ × base × height\n• Square: A = s²",
                            'assignment' => [
                                'title'       => '2D Shapes: Perimeter and Area',
                                'description' => "1. Find the perimeter and area of:\n   a) A rectangle 12 cm long and 8 cm wide\n   b) A square with side 9 cm\n\n2. Find the area of triangles with:\n   a) Base = 10 cm, Height = 6 cm\n   b) Base = 14 cm, Height = 7 cm\n\n3. A circle has radius 7 cm. Find:\n   a) Its diameter\n   b) Its circumference (use π = 22/7)\n   c) Its area (use π = 22/7)\n\n4. Classify each triangle:\n   a) Sides: 5 cm, 5 cm, 7 cm\n   b) Sides: 6 cm, 6 cm, 6 cm\n   c) Sides: 3 cm, 4 cm, 5 cm (note: 3² + 4² = 5²)\n\n5. How many lines of symmetry does a rectangle have?",
                                'due_days'    => 7,
                                'max_points'  => 30,
                                'pass_mark'   => 15,
                            ],
                        ],
                    ],
                    'quiz' => [
                        'title'              => 'Basic Geometry – Quiz',
                        'description'        => 'Quiz on types of lines, angles and properties of 2D shapes.',
                        'time_limit_minutes' => 20,
                        'questions'          => [
                            [
                                'prompt'  => 'An angle of 135° is classified as:',
                                'options' => [
                                    ['text' => 'Acute',   'correct' => false],
                                    ['text' => 'Right',   'correct' => false],
                                    ['text' => 'Obtuse',  'correct' => true],
                                    ['text' => 'Reflex',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Angles on a straight line add up to:',
                                'options' => [
                                    ['text' => '90°',  'correct' => false],
                                    ['text' => '180°', 'correct' => true],
                                    ['text' => '270°', 'correct' => false],
                                    ['text' => '360°', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A triangle with all three sides equal is called:',
                                'options' => [
                                    ['text' => 'Scalene',     'correct' => false],
                                    ['text' => 'Isosceles',   'correct' => false],
                                    ['text' => 'Equilateral', 'correct' => true],
                                    ['text' => 'Right-angled','correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The area of a rectangle 12 cm × 8 cm is:',
                                'options' => [
                                    ['text' => '20 cm²',  'correct' => false],
                                    ['text' => '40 cm²',  'correct' => false],
                                    ['text' => '96 cm²',  'correct' => true],
                                    ['text' => '108 cm²', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The diameter of a circle with radius 7 cm is:',
                                'options' => [
                                    ['text' => '7 cm',  'correct' => false],
                                    ['text' => '14 cm', 'correct' => true],
                                    ['text' => '21 cm', 'correct' => false],
                                    ['text' => '49 cm', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Lines that never meet and are always the same distance apart are:',
                                'options' => [
                                    ['text' => 'Perpendicular', 'correct' => false],
                                    ['text' => 'Intersecting',  'correct' => false],
                                    ['text' => 'Parallel',      'correct' => true],
                                    ['text' => 'Transversal',   'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The sum of interior angles of a quadrilateral is:',
                                'options' => [
                                    ['text' => '180°', 'correct' => false],
                                    ['text' => '270°', 'correct' => false],
                                    ['text' => '360°', 'correct' => true],
                                    ['text' => '540°', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Area of a triangle with base 10 cm and height 6 cm is:',
                                'options' => [
                                    ['text' => '60 cm²', 'correct' => false],
                                    ['text' => '30 cm²', 'correct' => true],
                                    ['text' => '16 cm²', 'correct' => false],
                                    ['text' => '50 cm²', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                    'exam' => [
                        'title'              => 'Basic Geometry – Term Examination',
                        'description'        => 'End-of-term geometry examination covering lines, angles and 2D shapes.',
                        'time_limit_minutes' => 45,
                        'questions'          => [
                            [
                                'prompt'  => 'Which angle is exactly 90°?',
                                'options' => [
                                    ['text' => 'Acute',   'correct' => false],
                                    ['text' => 'Right',   'correct' => true],
                                    ['text' => 'Obtuse',  'correct' => false],
                                    ['text' => 'Reflex',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Two angles on a straight line are x and 3x. What is x?',
                                'options' => [
                                    ['text' => '30°', 'correct' => false],
                                    ['text' => '45°', 'correct' => true],
                                    ['text' => '60°', 'correct' => false],
                                    ['text' => '90°', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The angles around a point are 90°, 120° and y. What is y?',
                                'options' => [
                                    ['text' => '120°', 'correct' => false],
                                    ['text' => '150°', 'correct' => true],
                                    ['text' => '100°', 'correct' => false],
                                    ['text' => '130°', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A quadrilateral with exactly one pair of parallel sides is a:',
                                'options' => [
                                    ['text' => 'Rectangle',    'correct' => false],
                                    ['text' => 'Parallelogram','correct' => false],
                                    ['text' => 'Trapezium',    'correct' => true],
                                    ['text' => 'Rhombus',      'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Circumference of a circle with radius 7 cm (π = 22/7) is:',
                                'options' => [
                                    ['text' => '22 cm',  'correct' => false],
                                    ['text' => '44 cm',  'correct' => true],
                                    ['text' => '154 cm', 'correct' => false],
                                    ['text' => '66 cm',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Area of a circle with radius 7 cm (π = 22/7) is:',
                                'options' => [
                                    ['text' => '44 cm²',  'correct' => false],
                                    ['text' => '154 cm²', 'correct' => true],
                                    ['text' => '22 cm²',  'correct' => false],
                                    ['text' => '49 cm²',  'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'A triangle with sides 5 cm, 5 cm and 7 cm is:',
                                'options' => [
                                    ['text' => 'Equilateral', 'correct' => false],
                                    ['text' => 'Isosceles',   'correct' => true],
                                    ['text' => 'Scalene',     'correct' => false],
                                    ['text' => 'Right-angled','correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'Perimeter of a square with side 9 cm is:',
                                'options' => [
                                    ['text' => '18 cm', 'correct' => false],
                                    ['text' => '27 cm', 'correct' => false],
                                    ['text' => '36 cm', 'correct' => true],
                                    ['text' => '81 cm', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'How many lines of symmetry does a rectangle (non-square) have?',
                                'options' => [
                                    ['text' => '1', 'correct' => false],
                                    ['text' => '2', 'correct' => true],
                                    ['text' => '4', 'correct' => false],
                                    ['text' => '0', 'correct' => false],
                                ],
                            ],
                            [
                                'prompt'  => 'The interior angles of an equilateral triangle are each:',
                                'options' => [
                                    ['text' => '45°', 'correct' => false],
                                    ['text' => '90°', 'correct' => false],
                                    ['text' => '60°', 'correct' => true],
                                    ['text' => '30°', 'correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
