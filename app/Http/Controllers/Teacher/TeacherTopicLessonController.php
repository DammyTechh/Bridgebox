<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherTopicLessonController extends Controller
{
    private function assertTopicAccess(Topic $topic): void
    {
        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $topic->school_class_id !== $teacherClassId) {
            abort(404);
        }
    }

    private function assertLessonTopic(Topic $topic, Lesson $lesson): void
    {
        if ($lesson->topic_id !== $topic->id) {
            abort(404);
        }
    }

    public function index(Topic $topic): View
    {
        $this->assertTopicAccess($topic);

        $lessons = $topic->lessons()
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('teacher.topics.lessons.index', [
            'topic' => $topic,
            'lessons' => $lessons,
        ]);
    }

    public function create(Topic $topic): View
    {
        $this->assertTopicAccess($topic);

        return view('teacher.topics.lessons.create', [
            'topic' => $topic,
        ]);
    }

    public function store(Request $request, Topic $topic): RedirectResponse
    {
        $this->assertTopicAccess($topic);

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:51200|mimetypes:application/pdf,video/mp4,video/webm,video/ogg',
        ]);

        $content = trim((string) ($data['content'] ?? ''));
        $file = $request->file('file');

        if ($content === '' && !$file) {
            return back()
                ->withErrors(['content' => 'Provide lesson text or upload a file.'])
                ->withInput();
        }

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $storedName = Str::uuid()->toString();
            if ($extension !== '') {
                $storedName .= '.' . $extension;
            }

            $filePath = $file->storeAs('lessons', $storedName, 'local');
            $fileName = $originalName;
            $fileType = $file->getClientMimeType();
        }

        $topic->lessons()->create([
            'title' => $data['title'],
            'content' => $content !== '' ? $content : null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
        ]);

        return redirect()
            ->route('teacher.topics.lessons.index', $topic)
            ->with([
                'status' => 'success',
                'message' => 'Lesson added to topic.',
            ]);
    }

    public function show(Topic $topic, Lesson $lesson): View
    {
        $this->assertTopicAccess($topic);
        $this->assertLessonTopic($topic, $lesson);

        return view('teacher.topics.lessons.show', [
            'topic' => $topic,
            'lesson' => $lesson,
        ]);
    }

    public function edit(Topic $topic, Lesson $lesson): View
    {
        $this->assertTopicAccess($topic);
        $this->assertLessonTopic($topic, $lesson);

        return view('teacher.topics.lessons.edit', [
            'topic' => $topic,
            'lesson' => $lesson,
        ]);
    }

    public function update(Request $request, Topic $topic, Lesson $lesson): RedirectResponse
    {
        $this->assertTopicAccess($topic);
        $this->assertLessonTopic($topic, $lesson);

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:51200|mimetypes:application/pdf,video/mp4,video/webm,video/ogg',
            'remove_file' => 'nullable|boolean',
        ]);

        $content = trim((string) ($data['content'] ?? ''));
        $file = $request->file('file');
        $removeFile = (bool) ($data['remove_file'] ?? false);

        // Must have either text content, an existing file (not being removed), or a new file
        if ($content === '' && !$file && ($removeFile || !$lesson->file_path)) {
            return back()
                ->withErrors(['content' => 'Provide lesson text or upload a file.'])
                ->withInput();
        }

        $filePath = $lesson->file_path;
        $fileName = $lesson->file_name;
        $fileType = $lesson->file_type;

        if ($removeFile && !$file) {
            // Delete the existing file
            if ($lesson->file_path && Storage::disk('local')->exists($lesson->file_path)) {
                Storage::disk('local')->delete($lesson->file_path);
            }
            $filePath = null;
            $fileName = null;
            $fileType = null;
        }

        if ($file) {
            // Delete the old file first
            if ($lesson->file_path && Storage::disk('local')->exists($lesson->file_path)) {
                Storage::disk('local')->delete($lesson->file_path);
            }

            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $storedName = Str::uuid()->toString();
            if ($extension !== '') {
                $storedName .= '.' . $extension;
            }

            $filePath = $file->storeAs('lessons', $storedName, 'local');
            $fileName = $originalName;
            $fileType = $file->getClientMimeType();
        }

        $lesson->update([
            'title' => $data['title'],
            'content' => $content !== '' ? $content : null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
        ]);

        return redirect()
            ->route('teacher.topics.lessons.index', $topic)
            ->with([
                'status' => 'success',
                'message' => 'Lesson updated.',
            ]);
    }

    public function download(Topic $topic, Lesson $lesson)
    {
        $this->assertTopicAccess($topic);
        $this->assertLessonTopic($topic, $lesson);

        if (!$lesson->file_path || !Storage::disk('local')->exists($lesson->file_path)) {
            abort(404);
        }

        $downloadName = $lesson->file_name ?: basename($lesson->file_path);

        return Storage::disk('local')->download($lesson->file_path, $downloadName);
    }

    public function destroy(Topic $topic, Lesson $lesson): RedirectResponse
    {
        $this->assertTopicAccess($topic);
        $this->assertLessonTopic($topic, $lesson);

        if ($lesson->file_path && Storage::disk('local')->exists($lesson->file_path)) {
            Storage::disk('local')->delete($lesson->file_path);
        }

        $lesson->delete();

        return back()->with([
            'status' => 'success',
            'message' => 'Lesson deleted.',
        ]);
    }

    public function byTopic(Request $request): JsonResponse
    {
        $topicId = $request->integer('topic_id');
        $teacherClassId = $request->user()?->school_class_id;

        if (!$topicId || !$teacherClassId) {
            return response()->json([]);
        }

        $topicMatches = Topic::query()
            ->whereKey($topicId)
            ->where('school_class_id', $teacherClassId)
            ->exists();
        if (!$topicMatches) {
            return response()->json([]);
        }

        $lessons = Lesson::query()
            ->where('topic_id', $topicId)
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json($lessons);
    }
}
