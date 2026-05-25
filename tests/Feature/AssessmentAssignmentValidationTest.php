<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class AssessmentAssignmentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_quiz_store_rejects_subject_outside_class_section(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $primary = $this->section('primary', 'Primary');
        $junior = $this->section('junior-secondary', 'Junior Secondary');

        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subjectJunior = Subject::factory()->create(['section_id' => $junior->id]);
        $topic = Topic::create([
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectJunior->id,
            'title' => 'Starter Topic',
            'description' => 'Mismatch topic',
        ]);

        $payload = [
            'title' => 'Quiz 1',
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectJunior->id,
            'topic_id' => $topic->id,
            'description' => 'Basics quiz',
            'time_limit_minutes' => 20,
            'total_mark' => 50,
            'pass_mark' => 25,
            'retake_attempts' => 1,
        ];

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), $payload)
            ->assertSessionHasErrors(['subject_id']);
    }

    public function test_admin_assignment_store_rejects_lesson_outside_topic(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $primary = $this->section('primary', 'Primary');
        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subjectPrimary = Subject::factory()->create(['section_id' => $primary->id]);

        $topicA = Topic::create([
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectPrimary->id,
            'title' => 'Topic A',
            'description' => 'Topic A',
        ]);
        $topicB = Topic::create([
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectPrimary->id,
            'title' => 'Topic B',
            'description' => 'Topic B',
        ]);

        $lessonFromOtherTopic = Lesson::create([
            'topic_id' => $topicB->id,
            'title' => 'Lesson B1',
            'content' => 'Lesson content',
        ]);

        $payload = [
            'title' => 'Assignment 1',
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectPrimary->id,
            'topic_id' => $topicA->id,
            'lesson_id' => $lessonFromOtherTopic->id,
            'description' => 'Assignment description',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_points' => 100,
            'pass_mark' => 50,
            'retake_attempts' => 0,
            'allow_late' => 0,
        ];

        $this->actingAs($admin)
            ->post(route('admin.assignments.store'), $payload)
            ->assertSessionHasErrors(['lesson_id']);
    }

    public function test_admin_quiz_store_rejects_topic_outside_class(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $primary = $this->section('primary', 'Primary');

        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $classOther = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subjectPrimary = Subject::factory()->create(['section_id' => $primary->id]);

        $topicOtherClass = Topic::create([
            'school_class_id' => $classOther->id,
            'subject_id' => $subjectPrimary->id,
            'title' => 'Other Class Topic',
            'description' => 'Topic in other class',
        ]);

        $payload = [
            'title' => 'Quiz 2',
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectPrimary->id,
            'topic_id' => $topicOtherClass->id,
            'description' => 'Basics quiz',
            'time_limit_minutes' => 20,
            'total_mark' => 50,
            'pass_mark' => 25,
            'retake_attempts' => 1,
        ];

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), $payload)
            ->assertSessionHasErrors(['topic_id']);
    }

    public function test_admin_assignment_store_succeeds_with_valid_chain(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $primary = $this->section('primary', 'Primary');
        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subjectPrimary = Subject::factory()->create(['section_id' => $primary->id]);

        $topic = Topic::create([
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectPrimary->id,
            'title' => 'Topic A',
            'description' => 'Topic A',
        ]);

        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'Lesson A',
            'content' => 'Lesson content',
        ]);

        $payload = [
            'title' => 'Assignment Valid',
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectPrimary->id,
            'topic_id' => $topic->id,
            'lesson_id' => $lesson->id,
            'description' => 'Assignment description',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_points' => 100,
            'pass_mark' => 50,
            'retake_attempts' => 0,
            'allow_late' => 0,
        ];

        $this->actingAs($admin)
            ->post(route('admin.assignments.store'), $payload)
            ->assertRedirect(route('admin.assignments.index'));

        $this->assertDatabaseHas('assignments', [
            'title' => 'Assignment Valid',
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_teacher_quiz_store_rejects_subject_outside_section(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $primary = $this->section('primary', 'Primary');
        $junior = $this->section('junior-secondary', 'Junior Secondary');

        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subjectJunior = Subject::factory()->create(['section_id' => $junior->id]);

        $topic = Topic::create([
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectJunior->id,
            'title' => 'Mismatch Topic',
            'description' => 'Mismatch topic',
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'school_class_id' => $classPrimary->id,
        ]);

        $payload = [
            'title' => 'Teacher Quiz',
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subjectJunior->id,
            'topic_id' => $topic->id,
            'description' => 'Basics quiz',
            'time_limit_minutes' => 15,
            'total_mark' => 40,
            'pass_mark' => 20,
            'retake_attempts' => 1,
        ];

        $this->actingAs($teacher)
            ->post(route('teacher.quizzes.store'), $payload)
            ->assertSessionHasErrors(['subject_id']);
    }

    public function test_teacher_assignment_store_blocks_other_class(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $primary = $this->section('primary', 'Primary');
        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $classOther = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subjectPrimary = Subject::factory()->create(['section_id' => $primary->id]);

        $topic = Topic::create([
            'school_class_id' => $classOther->id,
            'subject_id' => $subjectPrimary->id,
            'title' => 'Other Topic',
            'description' => 'Other class topic',
        ]);

        $lesson = Lesson::create([
            'topic_id' => $topic->id,
            'title' => 'Other Lesson',
            'content' => 'Other lesson content',
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'school_class_id' => $classPrimary->id,
        ]);

        $payload = [
            'title' => 'Teacher Assignment',
            'school_class_id' => $classOther->id,
            'subject_id' => $subjectPrimary->id,
            'topic_id' => $topic->id,
            'lesson_id' => $lesson->id,
            'description' => 'Assignment',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_points' => 100,
            'pass_mark' => 50,
            'retake_attempts' => 0,
            'allow_late' => 0,
        ];

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.store'), $payload)
            ->assertStatus(404);
    }

    private function section(string $slug, string $name): Section
    {
        return Section::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'description' => $name . ' section']
        );
    }
}
