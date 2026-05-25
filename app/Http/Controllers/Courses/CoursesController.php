<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\InstallMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CoursesController extends Controller
{
    public function __construct(private InstallMode $mode) {}

    public function index(): View|RedirectResponse
    {
        if (!$this->mode->isGeneric()) {
            return redirect()->route('landing');
        }

        $subjects = Subject::withCount(['topics', 'topics as lessons_count' => function ($q) {
            $q->join('lessons', 'lessons.topic_id', '=', 'topics.id');
        }])
            ->orderBy('name')
            ->get();

        return view('courses.index', compact('subjects'));
    }

    public function show(Subject $subject): View|RedirectResponse
    {
        if (!$this->mode->isGeneric()) {
            return redirect()->route('landing');
        }

        $topics = $subject->topics()->with('lessons')->orderBy('title')->get();

        return view('courses.show', compact('subject', 'topics'));
    }

    public function lesson(Subject $subject, Topic $topic, Lesson $lesson): View|RedirectResponse
    {
        if (!$this->mode->isGeneric()) {
            return redirect()->route('landing');
        }

        // Verify the topic belongs to this subject and the lesson belongs to the topic
        abort_unless($topic->subject_id === $subject->id, 404);
        abort_unless($lesson->topic_id === $topic->id, 404);

        // Previous / next lessons within the topic
        $lessons = $topic->lessons()->orderBy('id')->get();
        $currentIndex = $lessons->search(fn($l) => $l->id === $lesson->id);
        $prev = $currentIndex > 0 ? $lessons[$currentIndex - 1] : null;
        $next = $currentIndex < $lessons->count() - 1 ? $lessons[$currentIndex + 1] : null;

        return view('courses.lesson', compact('subject', 'topic', 'lesson', 'prev', 'next'));
    }

    public function image(Subject $subject): mixed
    {
        abort_unless($subject->feature_image && Storage::disk('local')->exists($subject->feature_image), 404);

        $path = Storage::disk('local')->path($subject->feature_image);
        $mime = mime_content_type($path) ?: 'image/jpeg';

        return response()->file($path, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function file(Subject $subject, Topic $topic, Lesson $lesson): mixed
    {
        if (!$this->mode->isGeneric()) {
            return redirect()->route('landing');
        }

        abort_unless($topic->subject_id === $subject->id, 404);
        abort_unless($lesson->topic_id === $topic->id, 404);
        abort_unless($lesson->file_path && Storage::disk('local')->exists($lesson->file_path), 404);

        $path = Storage::disk('local')->path($lesson->file_path);
        $mime = $lesson->file_type ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $lesson->file_name . '"',
        ]);
    }

    public function download(Subject $subject, Topic $topic, Lesson $lesson): mixed
    {
        if (!$this->mode->isGeneric()) {
            return redirect()->route('landing');
        }

        abort_unless($topic->subject_id === $subject->id, 404);
        abort_unless($lesson->topic_id === $topic->id, 404);
        abort_unless($lesson->file_path && Storage::disk('local')->exists($lesson->file_path), 404);

        return Storage::disk('local')->download($lesson->file_path, $lesson->file_name);
    }
}
