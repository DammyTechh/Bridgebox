<?php

namespace App\Http\Controllers\Admin;

use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\InstallMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class AdminAssessmentController extends Controller
{
    public function index(Request $request, string $type): View
    {
        $this->assertType($type);
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');

        $assessments = Assessment::query()
            ->with(['schoolClass', 'subject', 'topic'])
            ->where('type', $type)
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
        if ($classId) {
            $sectionId = SchoolClass::whereKey($classId)->value('section_id');
            if ($sectionId) {
                $subjectsQuery->where('section_id', $sectionId);
            }
        }

        return view('admin.assessments.index', [
            'assessments' => $assessments,
            'search' => $search,
            'type' => $type,
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => $subjectsQuery->get(),
            'selectedClassId' => $classId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
            'selectedTopicId' => $topicId ?: null,
        ]);
    }

    public function create(string $type): View
    {
        $this->assertType($type);
        $isGeneric = app(InstallMode::class)->isGeneric();

        return view('admin.assessments.create', [
            'classes'  => $isGeneric ? collect() : SchoolClass::orderBy('name')->get(),
            'subjects' => $isGeneric ? Subject::orderBy('name')->get() : collect(),
            'type'     => $type,
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->assertType($type);
        $isGeneric = app(InstallMode::class)->isGeneric();

        $data = $request->validate([
            'title'              => 'required|string|max:191',
            'school_class_id'    => $isGeneric ? 'nullable|integer|exists:school_classes,id' : 'required|integer|exists:school_classes,id',
            'subject_id'         => 'required|integer|exists:subjects,id',
            'topic_id'           => 'required|integer|exists:topics,id',
            'description'        => 'required|string',
            'time_limit_minutes' => 'required|integer|min:1|max:600',
            'total_mark'         => 'required|integer|min:1|max:1000',
            'pass_mark'          => 'required|integer|min:0|lte:total_mark',
            'retake_attempts'    => 'required|integer|min:0|max:100',
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

        $data['type'] = $type;

        Assessment::create($data);

        return redirect()
            ->route($this->routePrefix($type) . '.index')
            ->with([
                'message' => ucfirst($type) . ' created successfully.',
                'status'  => 'success',
            ]);
    }

    public function edit(Assessment $assessment, string $type): View
    {
        $this->assertType($type, $assessment);
        $isGeneric = app(InstallMode::class)->isGeneric();

        return view('admin.assessments.edit', [
            'assessment' => $assessment->load(['schoolClass', 'subject', 'topic']),
            'classes'    => $isGeneric ? collect() : SchoolClass::orderBy('name')->get(),
            'subjects'   => $isGeneric ? Subject::orderBy('name')->get() : collect(),
            'type'       => $type,
        ]);
    }

    public function update(Request $request, Assessment $assessment, string $type): RedirectResponse
    {
        $this->assertType($type, $assessment);

        $isGeneric = app(InstallMode::class)->isGeneric();

        $data = $request->validate([
            'title'              => 'required|string|max:191',
            'school_class_id'    => $isGeneric ? 'nullable|integer|exists:school_classes,id' : 'required|integer|exists:school_classes,id',
            'subject_id'         => 'required|integer|exists:subjects,id',
            'topic_id'           => 'required|integer|exists:topics,id',
            'description'        => 'required|string',
            'time_limit_minutes' => 'required|integer|min:1|max:600',
            'total_mark'         => 'required|integer|min:1|max:1000',
            'pass_mark'          => 'required|integer|min:0|lte:total_mark',
            'retake_attempts'    => 'required|integer|min:0|max:100',
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
        return $type === Assessment::TYPE_EXAM ? 'admin.exams' : 'admin.quizzes';
    }
}
