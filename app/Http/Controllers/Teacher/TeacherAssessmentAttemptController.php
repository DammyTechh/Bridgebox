<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAssessmentAttemptController extends Controller
{
    public function index(Request $request, string $type): View
    {
        $this->assertType($type);

        $teacher = $request->user();
        $teacherClassId = $teacher?->school_class_id;
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');
        $assessmentId = $request->integer('assessment_id');
        $studentId = $request->integer('student_id');

        $attempts = AssessmentAttempt::query()
            ->with(['assessment.subject', 'assessment.topic', 'assessment.schoolClass', 'user'])
            ->whereHas('assessment', function ($query) use ($type, $teacherClassId) {
                $query->where('type', $type)
                    ->where('school_class_id', $teacherClassId);
            })
            ->when($classId, function ($query) use ($classId, $teacherClassId) {
                if (!$teacherClassId || $classId !== $teacherClassId) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($subjectId, function ($query) use ($subjectId) {
                $query->whereHas('assessment', function ($assessmentQuery) use ($subjectId) {
                    $assessmentQuery->where('subject_id', $subjectId);
                });
            })
            ->when($topicId, function ($query) use ($topicId) {
                $query->whereHas('assessment', function ($assessmentQuery) use ($topicId) {
                    $assessmentQuery->where('topic_id', $topicId);
                });
            })
            ->when($assessmentId, function ($query) use ($assessmentId) {
                $query->where('assessment_id', $assessmentId);
            })
            ->when($studentId, function ($query) use ($studentId) {
                $query->where('user_id', $studentId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('assessment', function ($assessmentQuery) use ($search) {
                        $assessmentQuery->where('title', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('completed_at')
            ->orderByDesc('started_at')
            ->paginate(10)
            ->withQueryString();

        $classes = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get()
            : collect();

        $subjectsQuery = Subject::orderBy('name');
        if ($teacherClassId) {
            $sectionId = SchoolClass::whereKey($teacherClassId)->value('section_id');
            if ($sectionId) {
                $subjectsQuery->where('section_id', $sectionId);
            } else {
                $subjectsQuery->whereRaw('1 = 0');
            }
        } else {
            $subjectsQuery->whereRaw('1 = 0');
        }
        $subjects = $subjectsQuery->get();

        $topics = collect();
        if ($subjectId && $teacherClassId) {
            $topics = Topic::query()
                ->where('school_class_id', $teacherClassId)
                ->where('subject_id', $subjectId)
                ->orderBy('title')
                ->get();
        }

        $assessments = Assessment::query()
            ->where('type', $type)
            ->where('school_class_id', $teacherClassId)
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($topicId, fn ($query) => $query->where('topic_id', $topicId))
            ->orderBy('title')
            ->get();

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('school_class_id', $teacherClassId)
            ->orderBy('name')
            ->get();

        return view('teacher.assessments.attempts.index', [
            'attempts' => $attempts,
            'search' => $search,
            'type' => $type,
            'classes' => $classes,
            'subjects' => $subjects,
            'topics' => $topics,
            'assessments' => $assessments,
            'students' => $students,
            'selectedClassId' => $classId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
            'selectedTopicId' => $topicId ?: null,
            'selectedAssessmentId' => $assessmentId ?: null,
            'selectedStudentId' => $studentId ?: null,
            'teacherClass' => $teacher?->schoolClass,
        ]);
    }

    public function show(AssessmentAttempt $attempt, string $type): View
    {
        $attempt->load([
            'assessment.subject',
            'assessment.topic',
            'assessment.schoolClass',
            'user',
            'answers.question.options',
        ]);

        $assessment = $attempt->assessment;
        $this->assertType($type, $assessment);

        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $assessment->school_class_id !== $teacherClassId) {
            abort(404);
        }

        $questions = $assessment->questions()->with('options')->orderBy('order')->get();
        $answers = $attempt->answers->keyBy('assessment_question_id');

        return view('teacher.assessments.attempts.show', [
            'attempt' => $attempt,
            'assessment' => $assessment,
            'questions' => $questions,
            'answers' => $answers,
            'type' => $type,
        ]);
    }

    private function assertType(string $type, ?Assessment $assessment = null): void
    {
        if (!in_array($type, [Assessment::TYPE_QUIZ, Assessment::TYPE_EXAM], true)) {
            abort(404);
        }

        if ($assessment && $assessment->type !== $type) {
            abort(404);
        }
    }
}
