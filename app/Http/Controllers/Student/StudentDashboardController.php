<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $classId = $student?->school_class_id;

        $lessonsCount = $classId
            ? Lesson::query()
                ->whereHas('topic', function ($query) use ($classId) {
                    $query->where('school_class_id', $classId);
                })
                ->count()
            : 0;

        $assignmentsCount = $classId
            ? Assignment::query()
                ->whereHas('lesson.topic', function ($query) use ($classId) {
                    $query->where('school_class_id', $classId);
                })
                ->count()
            : 0;

        $quizzesCount = $classId
            ? Assessment::where('type', Assessment::TYPE_QUIZ)
                ->where('school_class_id', $classId)
                ->count()
            : 0;

        $examsCount = $classId
            ? Assessment::where('type', Assessment::TYPE_EXAM)
                ->where('school_class_id', $classId)
                ->count()
            : 0;

        $topicsCount = $classId
            ? Topic::where('school_class_id', $classId)->count()
            : 0;

        $sectionId = $classId ? SchoolClass::whereKey($classId)->value('section_id') : null;

        $subjects = Subject::query()
            ->withCount(['topics' => function ($query) use ($classId) {
                $query->where('school_class_id', $classId);
            }])
            ->whereIn('id', Topic::query()
                ->where('school_class_id', $classId)
                ->select('subject_id')
                ->distinct())
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            ->orderBy('name')
            ->get();

        $recentAssignments = $classId
            ? Assignment::query()
                ->with(['lesson.topic.subject', 'submissions' => fn ($q) => $q->where('user_id', $student?->id)])
                ->whereHas('lesson.topic', fn ($q) => $q->where('school_class_id', $classId))
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
            : collect();

        $recentQuizzes = $classId
            ? Assessment::query()
                ->with(['subject', 'topic'])
                ->where('type', Assessment::TYPE_QUIZ)
                ->where('school_class_id', $classId)
                ->withCount(['attempts as completed_attempts_count' => fn ($q) => $q->where('user_id', $student?->id)->where('status', 'completed')])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
            : collect();

        $recentExams = $classId
            ? Assessment::query()
                ->with(['subject', 'topic'])
                ->where('type', Assessment::TYPE_EXAM)
                ->where('school_class_id', $classId)
                ->withCount(['attempts as completed_attempts_count' => fn ($q) => $q->where('user_id', $student?->id)->where('status', 'completed')])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
            : collect();

        $completedLessonIds = $student
            ? LessonCompletion::where('user_id', $student->id)->pluck('lesson_id')->all()
            : [];

        $nextLesson = $classId
            ? Lesson::query()
                ->whereHas('topic', fn ($q) => $q->where('school_class_id', $classId))
                ->when(count($completedLessonIds) > 0, fn ($q) => $q->whereNotIn('id', $completedLessonIds))
                ->orderBy('created_at')
                ->first()
            : null;

        return view('dashboards.student', [
            'student' => $student,
            'lessonsCount' => $lessonsCount,
            'assignmentsCount' => $assignmentsCount,
            'quizzesCount' => $quizzesCount,
            'examsCount' => $examsCount,
            'topicsCount' => $topicsCount,
            'subjects' => $subjects,
            'recentAssignments' => $recentAssignments,
            'recentQuizzes' => $recentQuizzes,
            'recentExams' => $recentExams,
            'nextLesson' => $nextLesson,
        ]);
    }
}
