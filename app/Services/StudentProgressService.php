<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;

class StudentProgressService
{
    public function build(User $student): array
    {
        $classId = $student->school_class_id;

        $lessonsCount = $classId
            ? Lesson::query()
                ->whereHas('topic', function ($query) use ($classId) {
                    $query->where('school_class_id', $classId);
                })
                ->count()
            : 0;

        $topicsCount = $classId
            ? Topic::where('school_class_id', $classId)->count()
            : 0;

        $assignmentsCount = $classId
            ? Assignment::query()
                ->whereHas('lesson.topic', function ($query) use ($classId) {
                    $query->where('school_class_id', $classId);
                })
                ->count()
            : 0;

        $submissionsCount = AssignmentSubmission::query()
            ->where('user_id', $student->id)
            ->count();

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

        $attemptsCompleted = AssessmentAttempt::query()
            ->where('user_id', $student->id)
            ->where('status', 'completed')
            ->count();

        $recentAttempts = AssessmentAttempt::query()
            ->with(['assessment.subject', 'assessment.topic'])
            ->where('user_id', $student->id)
            ->orderByDesc('completed_at')
            ->limit(10)
            ->get();

        $recentSubmissions = AssignmentSubmission::query()
            ->with(['assignment.lesson.topic'])
            ->where('user_id', $student->id)
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get();

        return [
            'lessonsCount' => $lessonsCount,
            'topicsCount' => $topicsCount,
            'assignmentsCount' => $assignmentsCount,
            'submissionsCount' => $submissionsCount,
            'quizzesCount' => $quizzesCount,
            'examsCount' => $examsCount,
            'attemptsCompleted' => $attemptsCompleted,
            'recentAttempts' => $recentAttempts,
            'recentSubmissions' => $recentSubmissions,
        ];
    }
}
