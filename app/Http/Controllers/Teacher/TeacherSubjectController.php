<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherSubjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $sectionId = $request->integer('section_id');
        $teacherClassId = $request->user()?->school_class_id;
        $teacherSectionId = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->value('section_id')
            : null;

        $subjects = Subject::query()
            ->with('section')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($teacherSectionId, fn ($q) => $q->where('section_id', $teacherSectionId), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $sections = $teacherSectionId
            ? Section::whereKey($teacherSectionId)->orderBy('name')->get()
            : collect();

        return view('teacher.subjects.index', [
            'subjects' => $subjects,
            'search' => $search,
            'sections' => $sections,
            'selectedSectionId' => $sectionId ?: null,
        ]);
    }

    public function create(): View
    {
        $teacherClassId = request()->user()?->school_class_id;
        $sectionId = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->value('section_id')
            : null;
        $sections = $sectionId
            ? Section::whereKey($sectionId)->orderBy('name')->get()
            : collect();

        return view('teacher.subjects.create', [
            'sections' => $sections,
            'selectedSectionId' => $sectionId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacherClassId = $request->user()?->school_class_id;
        $sectionId = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->value('section_id')
            : null;

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $data['code'] = Str::slug($data['name']);
        $data['section_id'] = $sectionId;

        if (!$sectionId) {
            return back()->with([
                'status' => 'error',
                'message' => 'Assign a class section to your account before creating subjects.',
            ]);
        }

        Subject::create($data);

        return redirect()->route('teacher.subjects.index')->with([
            'message' => 'Subject created successfully.',
            'status' => 'success',
        ]);
    }

    public function edit(Subject $subject): View
    {
        $teacherClassId = request()->user()?->school_class_id;
        $sectionId = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->value('section_id')
            : null;
        if (!$sectionId || $subject->section_id !== $sectionId) {
            abort(404);
        }

        return view('teacher.subjects.edit', [
            'subject' => $subject,
            'sections' => Section::whereKey($sectionId)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $teacherClassId = $request->user()?->school_class_id;
        $sectionId = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->value('section_id')
            : null;
        if (!$sectionId || $subject->section_id !== $sectionId) {
            abort(404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $data['code'] = Str::slug($data['name']);
        $data['section_id'] = $sectionId;

        $subject->update($data);

        return redirect()->route('teacher.subjects.index')->with([
            'message' => 'Subject updated successfully.',
            'status' => 'success',
        ]);
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $teacherClassId = auth()->user()?->school_class_id;
        $sectionId = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->value('section_id')
            : null;
        if (!$sectionId || $subject->section_id !== $sectionId) {
            abort(404);
        }

        $subject->delete();

        return back()->with([
            'message' => 'Subject deleted.',
            'status' => 'success',
        ]);
    }

    public function byClass(Request $request)
    {
        $classId = $request->integer('class_id');
        $teacherClassId = $request->user()?->school_class_id;
        if (!$classId || !$teacherClassId || $classId !== $teacherClassId) {
            return response()->json([]);
        }

        $sectionId = SchoolClass::whereKey($teacherClassId)->value('section_id');
        if (!$sectionId) {
            return response()->json([]);
        }

        $subjects = Subject::query()
            ->where('section_id', $sectionId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subjects);
    }
}
