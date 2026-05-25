<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherTopicController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $teacherClassId = $request->user()?->school_class_id;

        $topics = Topic::query()
            ->with(['schoolClass', 'subject'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($teacherClassId, fn ($q) => $q->where('school_class_id', $teacherClassId), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($classId, function ($q) use ($classId, $teacherClassId) {
                if (!$teacherClassId || $classId !== $teacherClassId) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($subjectId, function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        $subjectsQuery = Subject::orderBy('name');
        if ($teacherClassId) {
            $sectionId = SchoolClass::whereKey($teacherClassId)->value('section_id');
            if ($sectionId) {
                $subjectsQuery->where('section_id', $sectionId);
            }
        } else {
            $subjectsQuery->whereRaw('1 = 0');
        }

        return view('teacher.topics.index', [
            'topics' => $topics,
            'search' => $search,
            'classes' => $teacherClassId ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get() : collect(),
            'subjects' => $subjectsQuery->get(),
            'selectedClassId' => $classId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
        ]);
    }

    public function create(): View
    {
        $teacherClassId = request()->user()?->school_class_id;
        $classes = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get()
            : collect();

        return view('teacher.topics.create', [
            'classes' => $classes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'school_class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'description' => 'nullable|string',
        ]);

        $teacherClassId = $request->user()?->school_class_id;
        if (!$teacherClassId || $teacherClassId !== (int) $data['school_class_id']) {
            abort(404);
        }

        $classSectionId = SchoolClass::whereKey($data['school_class_id'])->value('section_id');
        $subjectSectionId = Subject::whereKey($data['subject_id'])->value('section_id');
        if ($classSectionId && $subjectSectionId && $classSectionId !== $subjectSectionId) {
            return back()->withErrors([
                'subject_id' => 'The selected subject does not belong to the class section.',
            ])->withInput();
        }

        Topic::create($data);

        return redirect()->route('teacher.topics.index')->with([
            'message' => 'Topic created successfully.',
            'status' => 'success',
        ]);
    }

    public function edit(Topic $topic): View
    {
        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $topic->school_class_id !== $teacherClassId) {
            abort(404);
        }

        return view('teacher.topics.edit', [
            'topic' => $topic->load(['schoolClass', 'subject']),
            'classes' => SchoolClass::whereKey($teacherClassId)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'school_class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'description' => 'nullable|string',
        ]);

        $teacherClassId = $request->user()?->school_class_id;
        if (!$teacherClassId || $teacherClassId !== (int) $data['school_class_id']) {
            abort(404);
        }

        $classSectionId = SchoolClass::whereKey($data['school_class_id'])->value('section_id');
        $subjectSectionId = Subject::whereKey($data['subject_id'])->value('section_id');
        if ($classSectionId && $subjectSectionId && $classSectionId !== $subjectSectionId) {
            return back()->withErrors([
                'subject_id' => 'The selected subject does not belong to the class section.',
            ])->withInput();
        }

        $topic->update($data);

        return redirect()->route('teacher.topics.index')->with([
            'message' => 'Topic updated successfully.',
            'status' => 'success',
        ]);
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        $teacherClassId = auth()->user()?->school_class_id;
        if (!$teacherClassId || $topic->school_class_id !== $teacherClassId) {
            abort(404);
        }

        $topic->delete();

        return back()->with([
            'message' => 'Topic deleted.',
            'status' => 'success',
        ]);
    }

    public function bySubject(Request $request): JsonResponse
    {
        $subjectId = $request->integer('subject_id');
        $classId = $request->integer('class_id');
        $teacherClassId = $request->user()?->school_class_id;

        if (!$subjectId || !$teacherClassId) {
            return response()->json([]);
        }

        if ($classId && $classId !== $teacherClassId) {
            return response()->json([]);
        }

        $query = Topic::query()
            ->where('subject_id', $subjectId)
            ->where('school_class_id', $teacherClassId);

        $topics = $query->orderBy('title')->get(['id', 'title']);

        return response()->json($topics);
    }

    public function lessonsByTopic(Request $request): JsonResponse
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
