<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\InstallMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentLessonController extends Controller
{
    public function __construct(private InstallMode $mode) {}
    public function index(Request $request): View
    {
        $student = $request->user();
        $classId = $student?->school_class_id;
        $sectionId = $classId ? SchoolClass::whereKey($classId)->value('section_id') : null;
        $search = $request->string('q')->trim()->toString();
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');

        $isGeneric = $this->mode->isGeneric();

        $subjects = Subject::query()
            ->whereIn('id', Topic::query()
                ->when(!$isGeneric, fn ($q) => $q->where('school_class_id', $classId))
                ->select('subject_id')
                ->distinct())
            ->when($sectionId && !$isGeneric, fn ($query) => $query->where('section_id', $sectionId))
            ->orderBy('name')
            ->get();

        $topics = collect();
        if ($subjectId && ($isGeneric || $classId)) {
            if (!$isGeneric && $sectionId) {
                $subjectMatches = Subject::query()
                    ->whereKey($subjectId)
                    ->where('section_id', $sectionId)
                    ->exists();
                if (!$subjectMatches) {
                    $subjectId = null;
                    $topicId = null;
                }
            }
        }
        if ($subjectId && ($isGeneric || $classId)) {
            $topics = Topic::query()
                ->when(!$isGeneric, fn ($q) => $q->where('school_class_id', $classId))
                ->where('subject_id', $subjectId)
                ->orderBy('title')
                ->get();
        }

        $lessons = Lesson::query()
            ->with(['topic.subject', 'topic.schoolClass'])
            ->when(!$isGeneric, function ($query) use ($classId) {
                $query->whereHas('topic', function ($q) use ($classId) {
                    $q->where('school_class_id', $classId);
                });
            })
            ->when($subjectId, function ($query) use ($subjectId) {
                $query->whereHas('topic', function ($topicQuery) use ($subjectId) {
                    $topicQuery->where('subject_id', $subjectId);
                });
            })
            ->when($topicId, function ($query) use ($topicId) {
                $query->where('topic_id', $topicId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString();

        $completedLessonIds = $student
            ? LessonCompletion::where('user_id', $student->id)->pluck('lesson_id')->all()
            : [];

        return view('student.lessons.index', [
            'student' => $student,
            'lessons' => $lessons,
            'subjects' => $subjects,
            'topics' => $topics,
            'search' => $search,
            'selectedSubjectId' => $subjectId ?: null,
            'selectedTopicId' => $topicId ?: null,
            'completedLessonIds' => $completedLessonIds,
        ]);
    }

    public function show(Request $request, Lesson $lesson): View
    {
        $student = $request->user();
        $this->assertLessonAccess($student?->school_class_id, $lesson);

        // Auto-mark as completed on first view
        LessonCompletion::firstOrCreate(
            ['user_id' => $student->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );

        $lesson->load(['topic.subject', 'topic.schoolClass', 'assignments']);

        $quizzes = Assessment::query()
            ->where('type', Assessment::TYPE_QUIZ)
            ->where('topic_id', $lesson->topic_id)
            ->when(!$this->mode->isGeneric(), fn ($q) => $q->where('school_class_id', $student->school_class_id))
            ->orderBy('title')
            ->get();

        return view('student.lessons.show', [
            'student' => $student,
            'lesson' => $lesson,
            'isCompleted' => true,
            'assignments' => $lesson->assignments,
            'quizzes' => $quizzes,
        ]);
    }

    public function file(Request $request, Lesson $lesson)
    {
        $student = $request->user();
        $this->assertLessonAccess($student?->school_class_id, $lesson);

        if (!$lesson->file_path || !Storage::disk('local')->exists($lesson->file_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($lesson->file_path);
        $filename = $lesson->file_name ?: basename($lesson->file_path);
        $mime = $lesson->file_type ?: null;

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function assertLessonAccess(?int $classId, Lesson $lesson): void
    {
        if ($this->mode->isGeneric()) {
            return;
        }
        if (!$classId || $lesson->topic?->school_class_id !== $classId) {
            abort(404);
        }
    }
}
