<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherClassController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $classes = SchoolClass::query()
            ->with('section')
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('teacher.classes.index', [
            'classes' => $classes,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('teacher.classes.create', [
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:school_classes,slug',
            'description' => 'nullable|string',
            'section_id' => 'required|integer|exists:sections,id',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        SchoolClass::create($data);

        return redirect()->route('teacher.classes.index')->with([
            'message' => 'Class created successfully.',
            'status' => 'success',
        ]);
    }

    public function edit(SchoolClass $class): View
    {
        return view('teacher.classes.edit', [
            'class' => $class,
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:school_classes,slug,' . $class->id,
            'description' => 'nullable|string',
            'section_id' => 'required|integer|exists:sections,id',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        $class->update($data);

        return redirect()->route('teacher.classes.index')->with([
            'message' => 'Class updated successfully.',
            'status' => 'success',
        ]);
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $class->delete();

        return back()->with([
            'message' => 'Class deleted.',
            'status' => 'success',
        ]);
    }
}
