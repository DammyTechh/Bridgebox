<?php

namespace App\Http\Controllers\Admin;

use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\InstallMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class AdminAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');

        $assignments = Assignment::query()
            ->with(['lesson.topic.subject', 'lesson.topic.schoolClass'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($classId || $subjectId || $topicId, function ($q) use ($classId, $subjectId, $topicId) {
                $q->whereHas('lesson.topic', function ($topicQuery) use ($classId, $subjectId, $topicId) {
                    if ($classId) {
                        $topicQuery->where('school_class_id', $classId);
                    }
                    if ($subjectId) {
                        $topicQuery->where('subject_id', $subjectId);
                    }
                    if ($topicId) {
                        $topicQuery->where('id', $topicId);
                    }
                });
            })
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString();

        $subjectsQuery = Subject::orderBy('name');
        if ($classId) {
            $sectionId = SchoolClass::whereKey($classId)->value('section_id');
            if ($sectionId) {
                $subjectsQuery->where('section_id', $sectionId);
            }
        }

        return view('admin.assignments.index', [
            'assignments' => $assignments,
            'search' => $search,
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => $subjectsQuery->get(),
            'selectedClassId' => $classId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
            'selectedTopicId' => $topicId ?: null,
        ]);
    }

    public function create(): View
    {
        $isGeneric = app(InstallMode::class)->isGeneric();

        return view('admin.assignments.create', [
            'classes'  => $isGeneric ? collect() : SchoolClass::orderBy('name')->get(),
            'subjects' => $isGeneric ? Subject::orderBy('name')->get() : collect(),
            'lessons'  => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'allow_late' => $request->boolean('allow_late') ? 1 : 0,
        ]);

        $isGeneric = app(InstallMode::class)->isGeneric();

        $data = $request->validate([
            'title'           => 'required|string|max:191',
            'school_class_id' => $isGeneric ? 'nullable|integer|exists:school_classes,id' : 'required|integer|exists:school_classes,id',
            'subject_id'      => 'required|integer|exists:subjects,id',
            'topic_id'        => 'required|integer|exists:topics,id',
            'lesson_id'       => 'required|integer|exists:lessons,id',
            'description'     => 'required|string',
            'due_at'          => 'required|date',
            'max_points'      => 'required|integer|min:1|max:1000',
            'pass_mark'       => 'required|integer|min:0|lte:max_points',
            'retake_attempts' => 'required|integer|min:0|max:100',
            'allow_late'      => 'boolean',
            'late_mark' => 'required_if:allow_late,1|nullable|integer|min:0|lte:max_points',
            'late_due_at' => 'required_if:allow_late,1|nullable|date|after:due_at',
        ]);

        $classSectionId = SchoolClass::whereKey($data['school_class_id'])->value('section_id');
        $subjectSectionId = Subject::whereKey($data['subject_id'])->value('section_id');
        if ($classSectionId && $subjectSectionId && $classSectionId !== $subjectSectionId) {
            return back()->withErrors([
                'subject_id' => 'The selected subject does not belong to the class section.',
            ])->withInput();
        }

        $topicMatches = Topic::query()
            ->whereKey($data['topic_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('school_class_id', $data['school_class_id'])
            ->exists();
        if (!$topicMatches) {
            return back()->withErrors([
                'topic_id' => 'The selected topic does not belong to the subject and class.',
            ])->withInput();
        }

        $lessonMatches = Lesson::query()
            ->whereKey($data['lesson_id'])
            ->where('topic_id', $data['topic_id'])
            ->exists();
        if (!$lessonMatches) {
            return back()->withErrors([
                'lesson_id' => 'The selected lesson does not belong to the topic.',
            ])->withInput();
        }

        $payload = Arr::only($data, [
            'title',
            'lesson_id',
            'description',
            'due_at',
            'max_points',
            'pass_mark',
            'retake_attempts',
            'allow_late',
            'late_mark',
            'late_due_at',
        ]);

        Assignment::create($payload);

        return redirect()->route('admin.assignments.index')->with([
            'message' => 'Assignment created successfully.',
            'status' => 'success',
        ]);
    }

    public function edit(Assignment $assignment): View
    {
        $assignment->load('lesson.topic.subject');
        $isGeneric = app(InstallMode::class)->isGeneric();
        $topicId = $assignment->lesson?->topic_id;
        $lessons = $topicId
            ? Lesson::query()
                ->where('topic_id', $topicId)
                ->orderBy('title')
                ->get()
            : collect();

        return view('admin.assignments.edit', [
            'assignment' => $assignment,
            'classes'    => $isGeneric ? collect() : SchoolClass::orderBy('name')->get(),
            'subjects'   => $isGeneric ? Subject::orderBy('name')->get() : collect(),
            'lessons'    => $lessons,
        ]);
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $request->merge([
            'allow_late' => $request->boolean('allow_late') ? 1 : 0,
        ]);

        $isGeneric = app(InstallMode::class)->isGeneric();

        $data = $request->validate([
            'title'           => 'required|string|max:191',
            'school_class_id' => $isGeneric ? 'nullable|integer|exists:school_classes,id' : 'required|integer|exists:school_classes,id',
            'subject_id'      => 'required|integer|exists:subjects,id',
            'topic_id'        => 'required|integer|exists:topics,id',
            'lesson_id'       => 'required|integer|exists:lessons,id',
            'description'     => 'required|string',
            'due_at'          => 'required|date',
            'max_points'      => 'required|integer|min:1|max:1000',
            'pass_mark'       => 'required|integer|min:0|lte:max_points',
            'retake_attempts' => 'required|integer|min:0|max:100',
            'allow_late'      => 'boolean',
            'late_mark'       => 'required_if:allow_late,1|nullable|integer|min:0|lte:max_points',
            'late_due_at'     => 'required_if:allow_late,1|nullable|date|after:due_at',
        ]);

        if (!$isGeneric) {
            $classSectionId   = SchoolClass::whereKey($data['school_class_id'])->value('section_id');
            $subjectSectionId = Subject::whereKey($data['subject_id'])->value('section_id');
            if ($classSectionId && $subjectSectionId && $classSectionId !== $subjectSectionId) {
                return back()->withErrors([
                    'subject_id' => 'The selected subject does not belong to the class section.',
                ])->withInput();
            }

            $topicMatches = Topic::query()
                ->whereKey($data['topic_id'])
                ->where('subject_id', $data['subject_id'])
                ->where('school_class_id', $data['school_class_id'])
                ->exists();
            if (!$topicMatches) {
                return back()->withErrors([
                    'topic_id' => 'The selected topic does not belong to the subject and class.',
                ])->withInput();
            }
        } else {
            $topicMatches = Topic::query()
                ->whereKey($data['topic_id'])
                ->where('subject_id', $data['subject_id'])
                ->exists();
            if (!$topicMatches) {
                return back()->withErrors([
                    'topic_id' => 'The selected topic does not belong to the course.',
                ])->withInput();
            }
        }

        $lessonMatches = Lesson::query()
            ->whereKey($data['lesson_id'])
            ->where('topic_id', $data['topic_id'])
            ->exists();
        if (!$lessonMatches) {
            return back()->withErrors([
                'lesson_id' => 'The selected lesson does not belong to the topic.',
            ])->withInput();
        }

        $payload = Arr::only($data, [
            'title',
            'lesson_id',
            'description',
            'due_at',
            'max_points',
            'pass_mark',
            'retake_attempts',
            'allow_late',
            'late_mark',
            'late_due_at',
        ]);

        $assignment->update($payload);

        return redirect()->route('admin.assignments.index')->with([
            'message' => 'Assignment updated successfully.',
            'status' => 'success',
        ]);
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with([
            'message' => 'Assignment deleted.',
            'status' => 'success',
        ]);
    }
}
