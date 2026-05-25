<?php

namespace App\Http\Controllers\Admin;

use App\Models\Topic;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\InstallMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class AdminTopicController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');

        $topics = Topic::query()
            ->with(['schoolClass', 'subject'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($classId, function ($q) use ($classId) {
                $q->where('school_class_id', $classId);
            })
            ->when($subjectId, function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        $subjectsQuery = Subject::orderBy('name');
        if ($classId) {
            $sectionId = SchoolClass::whereKey($classId)->value('section_id');
            if ($sectionId) {
                $subjectsQuery->where('section_id', $sectionId);
            }
        }

        return view('admin.topics.index', [
            'topics' => $topics,
            'search' => $search,
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => $subjectsQuery->get(),
            'selectedClassId' => $classId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
        ]);
    }

    public function create(): View
    {
        $isGeneric = app(InstallMode::class)->isGeneric();
        return view('admin.topics.create', [
            'classes'  => SchoolClass::orderBy('name')->get(),
            'subjects' => $isGeneric ? Subject::orderBy('name')->get() : collect(),
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

        $classSectionId = SchoolClass::whereKey($data['school_class_id'])->value('section_id');
        $subjectSectionId = Subject::whereKey($data['subject_id'])->value('section_id');
        if ($classSectionId && $subjectSectionId && $classSectionId !== $subjectSectionId) {
            return back()->withErrors([
                'subject_id' => 'The selected subject does not belong to the class section.',
            ])->withInput();
        }

        Topic::create($data);

        return redirect()->route('admin.topics.index')->with([
            'message' => 'Topic created successfully.',
            'status' => 'success',
        ]);
    }

    public function edit(Topic $topic): View
    {
        $isGeneric = app(InstallMode::class)->isGeneric();
        return view('admin.topics.edit', [
            'topic'    => $topic->load(['schoolClass', 'subject']),
            'classes'  => SchoolClass::orderBy('name')->get(),
            'subjects' => $isGeneric ? Subject::orderBy('name')->get() : collect(),
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

        $classSectionId = SchoolClass::whereKey($data['school_class_id'])->value('section_id');
        $subjectSectionId = Subject::whereKey($data['subject_id'])->value('section_id');
        if ($classSectionId && $subjectSectionId && $classSectionId !== $subjectSectionId) {
            return back()->withErrors([
                'subject_id' => 'The selected subject does not belong to the class section.',
            ])->withInput();
        }

        $topic->update($data);

        return redirect()->route('admin.topics.index')->with([
            'message' => 'Topic updated successfully.',
            'status' => 'success',
        ]);
    }

    public function destroy(Topic $topic): RedirectResponse
    {
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

        if (!$subjectId) {
            return response()->json([]);
        }

        $query = Topic::query()
            ->where('subject_id', $subjectId);

        if ($classId) {
            $query->where('school_class_id', $classId);
        }

        $topics = $query->orderBy('title')->get(['id', 'title']);

        return response()->json($topics);
    }
}
