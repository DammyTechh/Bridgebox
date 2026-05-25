<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeacherAssignmentSubmissionController extends Controller
{
    public function index(Request $request, Assignment $assignment): View
    {
        $this->assertAssignmentAccess($assignment);

        $submissions = AssignmentSubmission::query()
            ->with('user')
            ->where('assignment_id', $assignment->id)
            ->orderByDesc('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('teacher.assignments.submissions.index', [
            'assignment' => $assignment->load('lesson.topic'),
            'submissions' => $submissions,
        ]);
    }

    public function show(Assignment $assignment, AssignmentSubmission $submission): View
    {
        $this->assertAssignmentAccess($assignment);
        if ($submission->assignment_id !== $assignment->id) {
            abort(404);
        }

        return view('teacher.assignments.submissions.show', [
            'assignment' => $assignment->load('lesson.topic'),
            'submission' => $submission->load('user'),
        ]);
    }

    public function update(Request $request, Assignment $assignment, AssignmentSubmission $submission): RedirectResponse
    {
        $this->assertAssignmentAccess($assignment);
        if ($submission->assignment_id !== $assignment->id) {
            abort(404);
        }

        $maxPoints = $assignment->max_points;
        $scoreRules = ['nullable', 'integer', 'min:0'];
        if ($maxPoints) {
            $scoreRules[] = 'max:' . $maxPoints;
        }

        $data = $request->validate([
            'score' => $scoreRules,
            'feedback' => ['nullable', 'string'],
        ]);

        $score = $data['score'] ?? null;
        $feedback = $data['feedback'] ?? null;

        $submission->score = $score;
        $submission->feedback = $feedback;
        if ($score !== null || ($feedback !== null && $feedback !== '')) {
            $submission->status = 'graded';
        }

        $submission->save();

        return back()->with([
            'status' => 'success',
            'message' => 'Submission graded successfully.',
        ]);
    }

    public function download(Assignment $assignment, AssignmentSubmission $submission)
    {
        $this->assertAssignmentAccess($assignment);
        if ($submission->assignment_id !== $assignment->id) {
            abort(404);
        }

        if (!$submission->file_path || !Storage::disk('local')->exists($submission->file_path)) {
            abort(404);
        }

        $downloadName = $submission->file_name ?: basename($submission->file_path);

        return Storage::disk('local')->download($submission->file_path, $downloadName);
    }

    public function destroy(Assignment $assignment, AssignmentSubmission $submission): RedirectResponse
    {
        $this->assertAssignmentAccess($assignment);
        if ($submission->assignment_id !== $assignment->id) {
            abort(404);
        }

        if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
            Storage::disk('local')->delete($submission->file_path);
        }

        $submission->delete();

        return back()->with([
            'status' => 'success',
            'message' => 'Submission deleted.',
        ]);
    }

    private function assertAssignmentAccess(Assignment $assignment): void
    {
        $teacherClassId = request()->user()?->school_class_id;
        $assignment->loadMissing('lesson.topic');
        if (!$teacherClassId || $assignment->lesson?->topic?->school_class_id !== $teacherClassId) {
            abort(404);
        }
    }
}
