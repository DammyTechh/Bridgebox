<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\SystemStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(Request $request, SystemStatusService $statusService): View
    {
        $teacher = $request->user();
        $classId = $teacher?->school_class_id;
        $teacherClass = $teacher?->schoolClass;

        $studentsCount = $classId
            ? User::where('role', User::ROLE_STUDENT)->where('school_class_id', $classId)->count()
            : 0;
        $classesCount = $classId ? 1 : 0;
        $subjectsCount = $teacherClass?->section_id
            ? Subject::where('section_id', $teacherClass->section_id)->count()
            : 0;
        $topicsCount = $classId ? Topic::where('school_class_id', $classId)->count() : 0;
        $assignmentsCount = $classId
            ? Assignment::whereHas('lesson.topic', function ($query) use ($classId) {
                $query->where('school_class_id', $classId);
            })->count()
            : 0;
        $quizzesCount = $classId
            ? Assessment::where('type', Assessment::TYPE_QUIZ)->where('school_class_id', $classId)->count()
            : 0;
        $examsCount = $classId
            ? Assessment::where('type', Assessment::TYPE_EXAM)->where('school_class_id', $classId)->count()
            : 0;

        $recentAssignments = $classId
            ? Assignment::with(['lesson.topic'])
                ->whereHas('lesson.topic', function ($query) use ($classId) {
                    $query->where('school_class_id', $classId);
                })
                ->latest()
                ->limit(4)
                ->get()
            : collect();

        $recentAssessments = $classId
            ? Assessment::with(['schoolClass', 'subject'])
                ->where('school_class_id', $classId)
                ->latest()
                ->limit(4)
                ->get()
            : collect();

        return view('dashboards.teacher', [
            'teacherClass' => $teacherClass,
            'status' => $statusService->snapshot(),
            'stats' => [
                'students' => $studentsCount,
                'classes' => $classesCount,
                'subjects' => $subjectsCount,
                'topics' => $topicsCount,
                'assignments' => $assignmentsCount,
                'quizzes' => $quizzesCount,
                'exams' => $examsCount,
            ],
            'recentAssignments' => $recentAssignments,
            'recentAssessments' => $recentAssessments,
        ]);
    }
}
