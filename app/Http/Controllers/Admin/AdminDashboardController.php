<?php

namespace App\Http\Controllers\Admin;

use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\AdminActionService;
use App\Services\SystemStatusService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    public function index(SystemStatusService $statusService, AdminActionService $actionService)
    {
        $status = $statusService->snapshot();
        $actionsEnabled = $actionService->isEnabled();
        $sudoAllowed = $actionService->isSudoAllowed();

        $stats = [
            'classes' => SchoolClass::count(),
            'teachers' => User::where('role', User::ROLE_TEACHER)->count(),
            'students' => User::where('role', User::ROLE_STUDENT)->count(),
            'subjects' => Subject::count(),
            'topics' => Topic::count(),
            'lessons' => Lesson::count(),
            'assignments' => Assignment::count(),
            'quizzes' => Assessment::where('type', Assessment::TYPE_QUIZ)->count(),
            'exams' => Assessment::where('type', Assessment::TYPE_EXAM)->count(),
        ];

        $sections = Section::query()
            ->withCount(['classes', 'subjects'])
            ->orderBy('name')
            ->get();

        return view('dashboards.admin', [
            'status' => $status,
            'actionsEnabled' => $actionsEnabled,
            'sudoAllowed' => $sudoAllowed,
            'stats' => $stats,
            'sections' => $sections,
        ]);
    }

    public function status(SystemStatusService $statusService): JsonResponse
    {
        return response()->json($statusService->snapshot());
    }
}
