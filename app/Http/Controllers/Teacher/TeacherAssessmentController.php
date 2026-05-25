<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAssessmentController extends Controller
{
    public function index(Request $request, string $type): View
    {
        $this->assertType($type);
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');
        $teacherClassId = $request->user()?->school_class_id;

        $assessments = Assessment::query()
            ->with(['schoolClass', 'subject', 'topic'])
            ->where('type', $type)
            ->when($teacherClassId, fn ($q) => $q->where('school_class_id', $teacherClassId), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($classId, function ($q) use ($classId) {
                $q->where('school_class_id', $classId);
            })
            ->when($subjectId, function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->when($topicId, function ($q) use ($topicId) {
                $q->where('topic_id', $topicId);
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

        return view('teacher.assessments.index', [
            'assessments' => $assessments,
            'search' => $search,
            'type' => $type,
            'classes' => $teacherClassId ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get() : collect(),
            'subjects' => $subjectsQuery->get(),
            'selectedClassId' => $classId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
            'selectedTopicId' => $topicId ?: null,
        ]);
    }

    public function create(string $type): View
    {
        $this->assertType($type);
        $teacherClassId = request()->user()?->school_class_id;

        return view('teacher.assessments.create', [
            'classes' => $teacherClassId ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get() : collect(),
            'type' => $type,
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->assertType($type);

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'school_class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'description' => 'required|string',
            'time_limit_minutes' => 'required|integer|min:1|max:600',
            'total_mark' => 'required|integer|min:1|max:1000',
            'pass_mark' => 'required|integer|min:0|lte:total_mark',
            'retake_attempts' => 'required|integer|min:0|max:100',
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

        $data['type'] = $type;

        Assessment::create($data);

        return redirect()
            ->route($this->routePrefix($type) . '.index')
            ->with([
                'message' => ucfirst($type) . ' created successfully.',
                'status' => 'success',
            ]);
    }

    public function edit(Assessment $assessment, string $type): View
    {
        $this->assertType($type, $assessment);
        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $assessment->school_class_id !== $teacherClassId) {
            abort(404);
        }

        return view('teacher.assessments.edit', [
            'assessment' => $assessment->load(['schoolClass', 'subject', 'topic']),
            'classes' => $teacherClassId ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get() : collect(),
            'type' => $type,
        ]);
    }

    public function update(Request $request, Assessment $assessment, string $type): RedirectResponse
    {
        $this->assertType($type, $assessment);

        $teacherClassId = $request->user()?->school_class_id;
        if (!$teacherClassId || $assessment->school_class_id !== $teacherClassId) {
            abort(404);
        }

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'school_class_id' => 'required|integer|exists:school_classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'topic_id' => 'required|integer|exists:topics,id',
            'description' => 'required|string',
            'time_limit_minutes' => 'required|integer|min:1|max:600',
            'total_mark' => 'required|integer|min:1|max:1000',
            'pass_mark' => 'required|integer|min:0|lte:total_mark',
            'retake_attempts' => 'required|integer|min:0|max:100',
        ]);

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

        $assessment->update($data);

        return redirect()
            ->route($this->routePrefix($type) . '.index')
            ->with([
                'message' => ucfirst($type) . ' updated successfully.',
                'status' => 'success',
            ]);
    }

    public function destroy(Assessment $assessment, string $type): RedirectResponse
    {
        $this->assertType($type, $assessment);
        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $assessment->school_class_id !== $teacherClassId) {
            abort(404);
        }

        $assessment->delete();

        return back()->with([
            'message' => ucfirst($type) . ' deleted.',
            'status' => 'success',
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
        return $type === Assessment::TYPE_EXAM ? 'teacher.exams' : 'teacher.quizzes';
    }
}
