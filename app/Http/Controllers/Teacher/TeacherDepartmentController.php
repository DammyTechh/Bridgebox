<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherDepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $departments = Department::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('teacher.departments.index', [
            'departments' => $departments,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('teacher.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $data['code'] = Str::slug($data['name']);

        Department::create($data);

        return redirect()->route('teacher.departments.index')->with([
            'message' => 'Department created successfully.',
            'status' => 'success',
        ]);
    }

    public function edit(Department $department): View
    {
        return view('teacher.departments.edit', [
            'department' => $department,
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $data['code'] = Str::slug($data['name']);

        $department->update($data);

        return redirect()->route('teacher.departments.index')->with([
            'message' => 'Department updated successfully.',
            'status' => 'success',
        ]);
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return back()->with([
            'message' => 'Department deleted.',
            'status' => 'success',
        ]);
    }
}
