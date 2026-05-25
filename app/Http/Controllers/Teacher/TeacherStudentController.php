<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherStudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $department = $request->string('department')->trim()->toString();
        $teacher = $request->user();
        $teacherClass = $teacher?->schoolClass;
        $teacherClassId = $teacher?->school_class_id;

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with(['studentProfile', 'schoolClass'])
            ->when($teacherClassId, function ($query) use ($teacherClassId) {
                $query->where('school_class_id', $teacherClassId);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($classId, function ($query) use ($classId, $teacherClassId) {
                if (!$teacherClassId || $classId !== $teacherClassId) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($department !== '', function ($query) use ($department) {
                $query->whereHas('studentProfile', function ($profileQuery) use ($department) {
                    $profileQuery->where('department', $department);
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $classes = $teacherClassId
            ? SchoolClass::whereKey($teacherClassId)->orderBy('name')->get()
            : collect();

        $departments = Department::orderBy('name')->pluck('name');

        return view('teacher.students.index', [
            'students' => $students,
            'search' => $search,
            'teacherClass' => $teacherClass,
            'classes' => $classes,
            'departments' => $departments,
            'selectedClassId' => $classId ?: null,
            'selectedDepartment' => $department !== '' ? $department : null,
        ]);
    }

    public function create(): View
    {
        $teacher = request()->user();
        $teacherClassId = $teacher?->school_class_id;
        if (!$teacherClassId) {
            return redirect()
                ->route('teacher.students.index')
                ->with([
                    'status' => 'error',
                    'message' => 'Assign a class to your account before managing students.',
                ]);
        }

        return view('teacher.users.students.create', [
            'classes' => SchoolClass::whereKey($teacherClassId)->orderBy('name')->get(),
            'teacherClassId' => $teacherClassId,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function show(User $user, StudentProgressService $progressService): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);
        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $user->school_class_id !== $teacherClassId) {
            abort(404);
        }

        $progress = $progressService->build($user);

        return view('teacher.students.show', [
            'student' => $user->load(['studentProfile', 'schoolClass']),
            ...$progress,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $request->user();
        $teacherClassId = $teacher?->school_class_id;
        if (!$teacherClassId) {
            return redirect()
                ->route('teacher.students.index')
                ->with([
                    'status' => 'error',
                    'message' => 'Assign a class to your account before managing students.',
                ]);
        }

        $request->merge([
            'auto_generate' => $request->boolean('auto_generate') ? 1 : 0,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:32'],
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id'), Rule::in([$teacherClassId])],
            'department' => ['nullable', 'string', 'max:255'],
            'admission_id' => ['nullable', 'string', 'max:255'],
            'auto_generate' => ['nullable', 'boolean'],
            'password' => ['required_unless:auto_generate,1', 'string', 'min:8', 'confirmed'],
        ]);

        $autoGenerate = $request->boolean('auto_generate');
        $plainPassword = $autoGenerate ? Str::random(12) : $request->string('password')->toString();
        $classId = (int) $data['school_class_id'];
        $selectedClass = $classId ? SchoolClass::find($classId) : null;
        $profileData = array_filter([
            'class' => $selectedClass?->name,
            'department' => $data['department'] ?? null,
            'admission_id' => $data['admission_id'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $student = DB::transaction(function () use ($data, $plainPassword, $profileData, $classId) {
            $student = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => User::ROLE_STUDENT,
                'school_class_id' => $classId ?: null,
                'password' => Hash::make($plainPassword),
            ]);

            if ($profileData) {
                $student->studentProfile()->create($profileData);
            }

            return $student;
        });

        return redirect()
            ->route('teacher.students.created', $student)
            ->with([
                'generated_password' => $autoGenerate ? $plainPassword : null,
                'password_mode' => $autoGenerate ? 'auto' : 'manual',
            ]);
    }

    public function created(User $user): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);
        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $user->school_class_id !== $teacherClassId) {
            abort(404);
        }

        return view('teacher.users.students.created', [
            'student' => $user,
            'profile' => $user->studentProfile,
            'generatedPassword' => session('generated_password'),
            'passwordMode' => session('password_mode', 'manual'),
        ]);
    }

    public function edit(User $user): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);
        $teacher = request()->user();
        $teacherClassId = $teacher?->school_class_id;
        if (!$teacherClassId || $user->school_class_id !== $teacherClassId) {
            abort(404);
        }

        return view('teacher.users.students.edit', [
            'student' => $user->load(['studentProfile', 'schoolClass']),
            'classes' => SchoolClass::whereKey($teacherClassId)->orderBy('name')->get(),
            'teacherClassId' => $teacherClassId,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function progress(User $user, StudentProgressService $progressService): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);
        $teacherClassId = request()->user()?->school_class_id;
        if (!$teacherClassId || $user->school_class_id !== $teacherClassId) {
            abort(404);
        }

        $progress = $progressService->build($user);

        return view('teacher.students.progress', [
            'student' => $user->load(['studentProfile', 'schoolClass']),
            ...$progress,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);
        $teacher = $request->user();
        $teacherClassId = $teacher?->school_class_id;
        if (!$teacherClassId || $user->school_class_id !== $teacherClassId) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id'), Rule::in([$teacherClassId])],
            'department' => ['nullable', 'string', 'max:255'],
            'admission_id' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $password = $request->string('password')->toString();
        $classId = (int) $data['school_class_id'];
        $selectedClass = SchoolClass::find($classId);

        DB::transaction(function () use ($user, $data, $password, $classId, $selectedClass) {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'school_class_id' => $classId,
            ]);

            if ($password !== '') {
                $user->password = Hash::make($password);
            }

            $user->save();

            $profileData = [
                'class' => $selectedClass?->name,
                'department' => ($data['department'] ?? '') !== '' ? $data['department'] : null,
                'admission_id' => ($data['admission_id'] ?? '') !== '' ? $data['admission_id'] : null,
            ];

            if ($user->studentProfile) {
                $user->studentProfile->update($profileData);
            } else {
                $user->studentProfile()->create($profileData);
            }
        });

        return redirect()
            ->route('teacher.students.index')
            ->with([
                'status' => 'success',
                'message' => 'Student updated successfully.',
            ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);
        $teacherClassId = auth()->user()?->school_class_id;
        if (!$teacherClassId || $user->school_class_id !== $teacherClassId) {
            abort(404);
        }

        if ($user->id === auth()->id()) {
            return back()->with([
                'status' => 'error',
                'message' => 'You cannot delete your own account.',
            ]);
        }

        $email = $user->email;
        $user->delete();

        return back()->with([
            'status' => 'success',
            'message' => "Deleted student account ({$email}).",
        ]);
    }
}
