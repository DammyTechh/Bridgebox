<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');
        $teacherClassId = $request->user()?->school_class_id;

        $assignments = Assignment::query()
            ->with(['lesson.topic.subject', 'lesson.topic.schoolClass'])
            ->when($teacherClassId, function ($q) use ($teacherClassId) {
                $q->whereHas('lesson.topic', function ($topicQuery) use ($teacherClassId) {
                    $topicQuery->where('school_class_id', $teacherClassId);
                });
            }, fn ($q) => $q->whereRaw('1 = 0'))
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
        if ($teacherClassId) {
            $sectionId = SchoolClass::whereKey($teacherClassId)->value('section_id');
            if ($sectionId) {
                $subjectsQuery->where('section_id', $sectionId);
            } else {
                $subjectsQuery->whereRaw('1 = 0');
            }
        } else {
            $subjectsQuery->whereRaw('1 = 0');
        }

        return view('teacher.assignments.index', [
            'assignments' => $assignments,
            'search' => $search,
            'classes' => $teacherClassId ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get() : collect(),
            'subjects' => $subjectsQuery->get(),
            'selectedClassId' => $classId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
            'selectedTopicId' => $topicId ?: null,
        ]);
    }

    public function create(): View
    {
        $teacherClassId = request()->user()?->school_class_id;

        return view('teacher.assignments.create', [
            'classes' => $teacherClassId ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get() : collect(),
            'lessons' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'allow_late' => $request->boolean('allow_late') ? 1 : 0,
        ]);

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'school_class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'description' => 'required|string',
            'due_at' => 'required|date',
            'max_points' => 'required|integer|min:1|max:1000',
            'pass_mark' => 'required|integer|min:0|lte:max_points',
            'retake_attempts' => 'required|integer|min:0|max:100',
            'allow_late' => 'boolean',
            'late_mark' => 'required_if:allow_late,1|nullable|integer|min:0|lte:max_points',
            'late_due_at' => 'required_if:allow_late,1|nullable|date|after:due_at',
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

        return redirect()->route('teacher.assignments.index')->with([
            'message' => 'Assignment created successfully.',
            'status' => 'success',
        ]);
    }

    public function edit(Assignment $assignment): View
    {
        $this->assertAssignmentAccess($assignment);
        $assignment->load('lesson.topic.subject');
        $teacherClassId = request()->user()?->school_class_id;
        $topicId = $assignment->lesson?->topic_id;
        $lessons = $topicId
            ? Lesson::query()
                ->where('topic_id', $topicId)
                ->orderBy('title')
                ->get()
            : collect();

        return view('teacher.assignments.edit', [
            'assignment' => $assignment,
            'classes' => $teacherClassId ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get() : collect(),
            'lessons' => $lessons,
        ]);
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->assertAssignmentAccess($assignment);
        $request->merge([
            'allow_late' => $request->boolean('allow_late') ? 1 : 0,
        ]);

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'school_class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'description' => 'required|string',
            'due_at' => 'required|date',
            'max_points' => 'required|integer|min:1|max:1000',
            'pass_mark' => 'required|integer|min:0|lte:max_points',
            'retake_attempts' => 'required|integer|min:0|max:100',
            'allow_late' => 'boolean',
            'late_mark' => 'required_if:allow_late,1|nullable|integer|min:0|lte:max_points',
            'late_due_at' => 'required_if:allow_late,1|nullable|date|after:due_at',
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

        $assignment->update($payload);

        return redirect()->route('teacher.assignments.index')->with([
            'message' => 'Assignment updated successfully.',
            'status' => 'success',
        ]);
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->assertAssignmentAccess($assignment);
        $assignment->delete();

        return back()->with([
            'message' => 'Assignment deleted.',
            'status' => 'success',
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
