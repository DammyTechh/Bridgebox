<?php

namespace App\Http\Controllers\Admin;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Support\InstallMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class AdminSubjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $sectionId = $request->integer('section_id');

        $subjects = Subject::query()
            ->with('section')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.subjects.index', [
            'subjects' => $subjects,
            'search' => $search,
            'sections' => Section::orderBy('name')->get(),
            'selectedSectionId' => $sectionId ?: null,
        ]);
    }

    public function create(): View
    {
        return view('admin.subjects.create', [
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $generic = app(InstallMode::class)->isGeneric();

        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'description'   => 'nullable|string',
            'section_id'    => $generic ? 'nullable' : 'required|integer|exists:sections,id',
            'feature_image' => 'nullable|image|max:4096',
        ]);

        $data['code'] = Str::slug($data['name']);
        if ($generic) {
            $data['section_id'] = null;
        }

        if ($request->hasFile('feature_image')) {
            $data['feature_image'] = $request->file('feature_image')
                ->store('subjects', 'local');
        } else {
            unset($data['feature_image']);
        }

        Subject::create($data);

        return redirect()->route('admin.subjects.index')->with([
            'message' => 'Subject created successfully.',
            'status'  => 'success',
        ]);
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject,
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $generic = app(InstallMode::class)->isGeneric();

        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'description'   => 'nullable|string',
            'section_id'    => $generic ? 'nullable' : 'required|integer|exists:sections,id',
            'feature_image' => 'nullable|image|max:4096',
            'remove_image'  => 'nullable|boolean',
        ]);

        $data['code'] = Str::slug($data['name']);
        if ($generic) {
            $data['section_id'] = null;
        }

        if ($request->hasFile('feature_image')) {
            // Delete old image if present
            if ($subject->feature_image) {
                Storage::disk('local')->delete($subject->feature_image);
            }
            $data['feature_image'] = $request->file('feature_image')
                ->store('subjects', 'local');
        } elseif ($request->boolean('remove_image') && $subject->feature_image) {
            Storage::disk('local')->delete($subject->feature_image);
            $data['feature_image'] = null;
        } else {
            unset($data['feature_image']);
        }

        unset($data['remove_image']);
        $subject->update($data);

        return redirect()->route('admin.subjects.index')->with([
            'message' => 'Subject updated successfully.',
            'status'  => 'success',
        ]);
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with([
            'message' => 'Subject deleted.',
            'status' => 'success',
        ]);
    }

    public function byClass(Request $request): JsonResponse
    {
        $classId = $request->integer('class_id');
        if (!$classId) {
            return response()->json([]);
        }

        $class = SchoolClass::find($classId);
        if (!$class) {
            return response()->json([]);
        }

        // In generic mode there are no sections - return all subjects
        if (app(InstallMode::class)->isGeneric() || !$class->section_id) {
            return response()->json(
                Subject::orderBy('name')->get(['id', 'name'])
            );
        }

        return response()->json(
            Subject::where('section_id', $class->section_id)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }
}
