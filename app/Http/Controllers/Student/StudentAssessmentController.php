<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAttemptAnswer;
use App\Models\AssessmentQuestion;
use App\Support\InstallMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentAssessmentController extends Controller
{
    public function __construct(private InstallMode $mode) {}
    public function index(Request $request, string $type): View
    {
        $this->assertType($type);

        $student = $request->user();
        $classId = $student?->school_class_id;
        $search = $request->string('q')->trim()->toString();

        $assessments = Assessment::query()
            ->with(['subject', 'topic'])
            ->withCount(['attempts as completed_attempts_count' => function ($query) use ($student) {
                $query->where('user_id', $student?->id)
                    ->where('status', 'completed');
            }])
            ->where('type', $type)
            ->when(!$this->mode->isGeneric(), fn ($q) => $q->where('school_class_id', $classId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('student.assessments.index', [
            'student' => $student,
            'assessments' => $assessments,
            'search' => $search,
            'type' => $type,
        ]);
    }

    public function start(Request $request, Assessment $assessment, string $type): RedirectResponse
    {
        $this->assertType($type, $assessment);
        $student = $request->user();
        $this->assertAssessmentAccess($student?->school_class_id, $assessment);

        $existingAttempt = AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $student?->id)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();

        if ($existingAttempt) {
            return redirect()->route($this->routePrefix($type) . '.attempt', $existingAttempt);
        }

        $maxAttempts = 1 + (int) ($assessment->retake_attempts ?? 0);
        $completedAttempts = AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $student?->id)
            ->where('status', 'completed')
            ->count();

        if ($completedAttempts >= $maxAttempts) {
            return redirect()
                ->route($this->routePrefix($type) . '.index')
                ->with([
                    'status' => 'error',
                    'message' => 'You have reached the maximum attempts for this assessment.',
                ]);
        }

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student?->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return redirect()->route($this->routePrefix($type) . '.attempt', $attempt);
    }

    public function attempt(AssessmentAttempt $attempt, string $type): View
    {
        $assessment = $attempt->assessment()->with(['questions.options' => function ($query) {
            $query->orderBy('order');
        }])->firstOrFail();

        $this->assertType($type, $assessment);
        $student = request()->user();
        $this->assertAttemptAccess($student?->id, $attempt);

        if ($attempt->status === 'completed') {
            return $this->result($attempt, $type);
        }

        if ($this->expireAttemptIfTimedOut($attempt, $assessment)) {
            return redirect()
                ->route($this->routePrefix($type) . '.result', $attempt)
                ->with([
                    'status' => 'error',
                    'message' => 'Time is up. Your attempt was submitted with a score of zero.',
                ]);
        }

        $questions = $assessment->questions()->orderBy('order')->get();

        return view('student.assessments.take', [
            'student' => $student,
            'assessment' => $assessment,
            'attempt' => $attempt,
            'questions' => $questions,
            'type' => $type,
        ]);
    }

    public function submit(Request $request, AssessmentAttempt $attempt, string $type): RedirectResponse
    {
        $assessment = $attempt->assessment()->with(['questions.options'])->firstOrFail();
        $this->assertType($type, $assessment);
        $student = $request->user();
        $this->assertAttemptAccess($student?->id, $attempt);

        if ($attempt->status === 'completed') {
            return redirect()->route($this->routePrefix($type) . '.result', $attempt);
        }

        if ($this->expireAttemptIfTimedOut($attempt, $assessment)) {
            return redirect()
                ->route($this->routePrefix($type) . '.result', $attempt)
                ->with([
                    'status' => 'error',
                    'message' => 'Time is up. Your attempt was submitted with a score of zero.',
                ]);
        }

        $answers = $request->input('answers', []);

        DB::transaction(function () use ($attempt, $assessment, $answers) {
            $attempt->answers()->delete();

            $score = 0;
            $total = 0;

            foreach ($assessment->questions as $question) {
                $total += (int) $question->points;
                $selectedOptionId = $answers[$question->id] ?? null;
                $selectedOption = $selectedOptionId
                    ? $question->options->firstWhere('id', (int) $selectedOptionId)
                    : null;

                $isCorrect = $selectedOption ? (bool) $selectedOption->is_correct : false;
                if ($isCorrect) {
                    $score += (int) $question->points;
                }

                AssessmentAttemptAnswer::create([
                    'assessment_attempt_id' => $attempt->id,
                    'assessment_question_id' => $question->id,
                    'assessment_option_id' => $selectedOption?->id,
                    'is_correct' => $isCorrect,
                ]);
            }

            $attempt->update([
                'completed_at' => now(),
                'score' => $score,
                'total' => $total,
                'status' => 'completed',
            ]);
        });

        return redirect()->route($this->routePrefix($type) . '.result', $attempt)
            ->with([
                'status' => 'success',
                'message' => 'Assessment submitted successfully.',
            ]);
    }

    public function forfeit(Request $request, AssessmentAttempt $attempt, string $type): RedirectResponse
    {
        $assessment = $attempt->assessment()->with(['questions'])->firstOrFail();
        $this->assertType($type, $assessment);
        $student = $request->user();
        $this->assertAttemptAccess($student?->id, $attempt);

        if ($attempt->status !== 'completed') {
            $this->completeAttemptWithZero($attempt, $assessment);
        }

        return redirect()
            ->route($this->routePrefix($type) . '.result', $attempt)
            ->with([
                'status' => 'error',
                'message' => 'You left the assessment. Your score is now zero.',
            ]);
    }

    public function result(AssessmentAttempt $attempt, string $type): View
    {
        $assessment = $attempt->assessment()->with(['subject', 'topic'])->firstOrFail();
        $this->assertType($type, $assessment);
        $student = request()->user();
        $this->assertAttemptAccess($student?->id, $attempt);

        $maxAttempts = 1 + (int) ($assessment->retake_attempts ?? 0);
        $completedAttempts = AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $student?->id)
            ->where('status', 'completed')
            ->count();

        return view('student.assessments.result', [
            'student' => $student,
            'assessment' => $assessment,
            'attempt' => $attempt,
            'type' => $type,
            'remainingAttempts' => max($maxAttempts - $completedAttempts, 0),
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

    private function routePrefix(string $type): string
    {
        return $type === Assessment::TYPE_EXAM ? 'student.exams' : 'student.quizzes';
    }

    private function assertAssessmentAccess(?int $classId, Assessment $assessment): void
    {
        if ($this->mode->isGeneric()) {
            return;
        }
        if (!$classId || $assessment->school_class_id !== $classId) {
            abort(404);
        }
    }

    private function assertAttemptAccess(?int $studentId, AssessmentAttempt $attempt): void
    {
        if (!$studentId || $attempt->user_id !== $studentId) {
            abort(404);
        }
    }

    private function expireAttemptIfTimedOut(AssessmentAttempt $attempt, Assessment $assessment): bool
    {
        if ($attempt->status === 'completed') {
            return false;
        }

        $timeLimit = (int) ($assessment->time_limit_minutes ?? 0);
        if ($timeLimit <= 0 || !$attempt->started_at) {
            return false;
        }

        $expiresAt = $attempt->started_at->copy()->addMinutes($timeLimit);
        if (!$expiresAt->isPast()) {
            return false;
        }

        $this->completeAttemptWithZero($attempt, $assessment);
        return true;
    }

    private function completeAttemptWithZero(AssessmentAttempt $attempt, Assessment $assessment): void
    {
        $total = (int) ($assessment->relationLoaded('questions')
            ? $assessment->questions->sum('points')
            : $assessment->questions()->sum('points'));

        DB::transaction(function () use ($attempt, $total) {
            $attempt->answers()->delete();
            $attempt->update([
                'completed_at' => now(),
                'score' => 0,
                'total' => $total,
                'status' => 'completed',
            ]);
        });
    }
}
