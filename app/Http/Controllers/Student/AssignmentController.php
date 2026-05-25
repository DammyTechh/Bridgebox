<?php

namespace App\Http\Controllers\Student;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\InstallMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class AssignmentController extends Controller
{
    public function __construct(private InstallMode $mode) {}
    public function index(Request $request): View
    {
        $student = $request->user();
        $classId = $student?->school_class_id;
        $search = $request->string('q')->trim()->toString();
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');

        $isGeneric = $this->mode->isGeneric();

        $subjects = Subject::query()
            ->whereIn('id', Topic::query()
                ->when(!$isGeneric, fn ($q) => $q->where('school_class_id', $classId))
                ->select('subject_id')
                ->distinct())
            ->orderBy('name')
            ->get();

        $topics = collect();
        if ($subjectId && ($isGeneric || $classId)) {
            $topics = Topic::query()
                ->when(!$isGeneric, fn ($q) => $q->where('school_class_id', $classId))
                ->where('subject_id', $subjectId)
                ->orderBy('title')
                ->get();
        }

        $assignments = Assignment::query()
            ->with([
                'lesson.topic.subject',
                'submissions' => function ($query) use ($student) {
                    $query->where('user_id', $student?->id);
                },
            ])
            ->when(!$isGeneric, function ($query) use ($classId) {
                $query->whereHas('lesson.topic', function ($q) use ($classId) {
                    $q->where('school_class_id', $classId);
                });
            })
            ->when($subjectId, function ($query) use ($subjectId) {
                $query->whereHas('lesson.topic', function ($topicQuery) use ($subjectId) {
                    $topicQuery->where('subject_id', $subjectId);
                });
            })
            ->when($topicId, function ($query) use ($topicId) {
                $query->whereHas('lesson', function ($lessonQuery) use ($topicId) {
                    $lessonQuery->where('topic_id', $topicId);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('due_at')
            ->paginate(10)
            ->withQueryString();

        return view('student.assignments.index', [
            'student' => $student,
            'assignments' => $assignments,
            'subjects' => $subjects,
            'topics' => $topics,
            'search' => $search,
            'selectedSubjectId' => $subjectId ?: null,
            'selectedTopicId' => $topicId ?: null,
        ]);
    }

    public function show(Assignment $assignment): View
    {
        $assignment->load('lesson.topic');

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', auth()->id())
            ->first();

        return view('student.assignments.show', [
            'student' => auth()->user(),
            'assignment' => $assignment,
            'submission' => $submission,
        ]);
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:51200|mimetypes:application/pdf,video/mp4,video/webm,video/ogg',
        ]);

        $content = trim((string) ($data['content'] ?? ''));
        $file = $request->file('file');

        if ($content === '' && !$file) {
            return back()
                ->withErrors(['content' => 'Provide assignment text or upload a file.'])
                ->withInput();
        }

        $submission = AssignmentSubmission::firstOrNew([
            'assignment_id' => $assignment->id,
            'user_id' => auth()->id(),
        ]);

        if ($file) {
            if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
                Storage::disk('local')->delete($submission->file_path);
            }

            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $storedName = Str::uuid()->toString();
            if ($extension !== '') {
                $storedName .= '.' . $extension;
            }

            $filePath = $file->storeAs('assignment-submissions', $storedName, 'local');
            $submission->file_path = $filePath;
            $submission->file_name = $originalName;
            $submission->file_type = $file->getClientMimeType();
        }

        $submission->content = $content !== '' ? $content : $submission->content;
        $submission->submitted_at = now();
        $submission->status = 'submitted';
        $submission->save();

        return back()->with([
            'status' => 'success',
            'message' => 'Assignment submitted successfully.',
        ]);
    }
}
