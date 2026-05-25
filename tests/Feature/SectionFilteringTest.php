<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_subjects_by_class_returns_only_section_subjects(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $primary = $this->section('primary', 'Primary');
        $junior = $this->section('junior-secondary', 'Junior Secondary');

        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        SchoolClass::factory()->create(['section_id' => $junior->id]);

        $primarySubject = Subject::factory()->create(['section_id' => $primary->id]);
        $juniorSubject = Subject::factory()->create(['section_id' => $junior->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.subjects.by-class', ['class_id' => $classPrimary->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => $primarySubject->id, 'name' => $primarySubject->name])
            ->assertJsonMissing(['id' => $juniorSubject->id, 'name' => $juniorSubject->name]);
    }

    public function test_teacher_subjects_by_class_restricts_to_teacher_class(): void
    {
        $primary = $this->section('primary', 'Primary');
        $junior = $this->section('junior-secondary', 'Junior Secondary');

        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $classJunior = SchoolClass::factory()->create(['section_id' => $junior->id]);

        $primarySubject = Subject::factory()->create(['section_id' => $primary->id]);
        Subject::factory()->create(['section_id' => $junior->id]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'school_class_id' => $classPrimary->id,
        ]);

        $this->actingAs($teacher)
            ->getJson(route('teacher.subjects.by-class', ['class_id' => $classPrimary->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => $primarySubject->id, 'name' => $primarySubject->name]);

        $this->actingAs($teacher)
            ->getJson(route('teacher.subjects.by-class', ['class_id' => $classJunior->id]))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_teacher_topics_by_subject_restricts_to_teacher_class(): void
    {
        $primary = $this->section('primary', 'Primary');
        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $classOther = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subject = Subject::factory()->create(['section_id' => $primary->id]);

        $topicPrimary = Topic::create([
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subject->id,
            'title' => 'Numbers',
            'description' => 'Number basics',
        ]);

        Topic::create([
            'school_class_id' => $classOther->id,
            'subject_id' => $subject->id,
            'title' => 'Geometry',
            'description' => 'Shapes and angles',
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'school_class_id' => $classPrimary->id,
        ]);

        $this->actingAs($teacher)
            ->getJson(route('teacher.topics.by-subject', [
                'subject_id' => $subject->id,
                'class_id' => $classPrimary->id,
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $topicPrimary->id, 'title' => $topicPrimary->title]);

        $this->actingAs($teacher)
            ->getJson(route('teacher.topics.by-subject', [
                'subject_id' => $subject->id,
                'class_id' => $classOther->id,
            ]))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_teacher_lessons_by_topic_restricts_to_teacher_class(): void
    {
        $primary = $this->section('primary', 'Primary');
        $classPrimary = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $classOther = SchoolClass::factory()->create(['section_id' => $primary->id]);
        $subject = Subject::factory()->create(['section_id' => $primary->id]);

        $topicPrimary = Topic::create([
            'school_class_id' => $classPrimary->id,
            'subject_id' => $subject->id,
            'title' => 'Topic Primary',
            'description' => 'Primary topic',
        ]);

        $topicOther = Topic::create([
            'school_class_id' => $classOther->id,
            'subject_id' => $subject->id,
            'title' => 'Topic Other',
            'description' => 'Other topic',
        ]);

        $topicPrimary->lessons()->create([
            'title' => 'Lesson A',
            'content' => 'Lesson content',
        ]);

        $topicOther->lessons()->create([
            'title' => 'Lesson B',
            'content' => 'Lesson content',
        ]);

        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'school_class_id' => $classPrimary->id,
        ]);

        $this->actingAs($teacher)
            ->getJson(route('teacher.topics.lessons.by-topic', ['topic_id' => $topicPrimary->id]))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Lesson A']);

        $this->actingAs($teacher)
            ->getJson(route('teacher.topics.lessons.by-topic', ['topic_id' => $topicOther->id]))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_admin_subjects_by_class_returns_empty_when_missing_class(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->getJson(route('admin.subjects.by-class'))
            ->assertOk()
            ->assertExactJson([]);
    }

    private function section(string $slug, string $name): Section
    {
        return Section::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'description' => $name . ' section']
        );
    }
}
