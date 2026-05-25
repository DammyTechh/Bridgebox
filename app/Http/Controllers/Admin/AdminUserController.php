<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\AdminActionLog;
use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class AdminUserController extends Controller
{
    private const MANAGED_ROLES = [
        User::ROLE_TEACHER,
        User::ROLE_STUDENT,
    ];

    public function teachers(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');

        $teachers = User::query()
            ->where('role', User::ROLE_TEACHER)
            ->with('schoolClass')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($classId, function ($query) use ($classId) {
                $query->where('school_class_id', $classId);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.teachers.index', [
            'teachers' => $teachers,
            'search' => $search,
            'classes' => SchoolClass::orderBy('name')->get(),
            'selectedClassId' => $classId ?: null,
        ]);
    }

    public function students(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with(['studentProfile', 'schoolClass'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($classId, function ($query) use ($classId) {
                $query->where('school_class_id', $classId);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.students.index', [
            'students' => $students,
            'search' => $search,
            'classes' => SchoolClass::orderBy('name')->get(),
            'selectedClassId' => $classId ?: null,
        ]);
    }

    public function createStudent(): View
    {
        return view('admin.users.students.create', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function bulkStudents(): View
    {
        return view('admin.users.students.bulk', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function storeBulkStudents(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');
        $path = $file?->getRealPath();

        if (!$path || !is_readable($path)) {
            return back()->with([
                'status' => 'error',
                'message' => 'Unable to read the CSV file. Please try again.',
            ]);
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with([
                'status' => 'error',
                'message' => 'Unable to open the CSV file. Please try again.',
            ]);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return back()->with([
                'status' => 'error',
                'message' => 'The CSV file is empty. Add a header row and try again.',
            ]);
        }

        $headerMap = $this->mapStudentCsvHeaders($headers);
        $missing = [];

        if (!isset($headerMap['name'])) {
            $missing[] = 'name';
        }
        if (!isset($headerMap['email'])) {
            $missing[] = 'email';
        }
        if (!isset($headerMap['school_class_id']) && !isset($headerMap['school_class'])) {
            $missing[] = 'school_class or school_class_id';
        }

        if ($missing) {
            fclose($handle);
            return back()->with([
                'status' => 'error',
                'message' => 'CSV headers missing: ' . implode(', ', $missing) . '.',
            ]);
        }

        $classes = SchoolClass::orderBy('name')->get();
        $classesById = $classes->keyBy('id');
        $classesByName = $classes->keyBy(fn ($class) => Str::lower($class->name));

        $created = 0;
        $skipped = 0;
        $errorRows = 0;
        $errors = [];
        $createdRows = [];
        $rowIndex = 1;
        $seenEmails = [];
        $maxErrors = 50;
        $maxCreatedRows = 50;

        while (($row = fgetcsv($handle)) !== false) {
            $rowIndex++;

            if ($this->isCsvRowEmpty($row)) {
                continue;
            }

            $name = $this->csvValue($row, $headerMap, 'name');
            $email = Str::lower($this->csvValue($row, $headerMap, 'email'));
            $phone = $this->csvValue($row, $headerMap, 'phone');
            $department = $this->csvValue($row, $headerMap, 'department');
            $admissionId = $this->csvValue($row, $headerMap, 'admission_id');

            $classId = (int) $this->csvValue($row, $headerMap, 'school_class_id');
            $className = $this->csvValue($row, $headerMap, 'school_class');

            if (!$classId && $className !== '') {
                $classId = (int) ($classesByName[Str::lower($className)]->id ?? 0);
            }

            $autoGenerate = false;
            if (isset($headerMap['auto_generate'])) {
                $autoGenerate = $this->csvTruthy($this->csvValue($row, $headerMap, 'auto_generate'));
            }

            $password = $this->csvValue($row, $headerMap, 'password');
            $shouldAutoGenerate = $autoGenerate || (!isset($headerMap['password']) && $password === '');

            if ($shouldAutoGenerate) {
                $password = Str::random(12);
            }

            $rowErrors = [];

            if ($name === '') {
                $rowErrors[] = 'Name is required.';
            }
            if ($email === '') {
                $rowErrors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Email is not valid.';
            } elseif (isset($seenEmails[$email])) {
                $rowErrors[] = 'Email is duplicated in the CSV.';
            } elseif (User::where('email', $email)->exists()) {
                $rowErrors[] = 'Email already exists in the system.';
            }

            if (!$classId || !$classesById->has($classId)) {
                $rowErrors[] = 'Class not found. Use an existing class name or ID.';
            }

            if ($password === '' && !$shouldAutoGenerate) {
                $rowErrors[] = 'Password is required unless auto_generate is 1.';
            }

            if ($rowErrors) {
                $errorRows++;
                $skipped++;
                if (count($errors) < $maxErrors) {
                    $errors[] = 'Row ' . $rowIndex . ': ' . implode(' ', $rowErrors);
                }
                continue;
            }

            $seenEmails[$email] = true;
            $selectedClass = $classesById->get($classId);

            $student = DB::transaction(function () use ($name, $email, $phone, $classId, $password, $department, $admissionId, $selectedClass) {
                $student = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'role' => User::ROLE_STUDENT,
                    'school_class_id' => $classId,
                    'password' => Hash::make($password),
                ]);

                $profileData = array_filter([
                    'class' => $selectedClass?->name,
                    'department' => $department !== '' ? $department : null,
                    'admission_id' => $admissionId !== '' ? $admissionId : null,
                ], static fn ($value) => $value !== null && $value !== '');

                if ($profileData) {
                    $student->studentProfile()->create($profileData);
                }

                return $student;
            });

            $created++;

            if (count($createdRows) < $maxCreatedRows) {
                $createdRows[] = [
                    'name' => $student->name,
                    'email' => $student->email,
                    'class' => $selectedClass?->name,
                    'password' => $password,
                ];
            }
        }

        fclose($handle);

        if ($created > 0) {
            $this->logAdminAction(
                'student_bulk_import',
                $errorRows > 0 ? 'warning' : 'success',
                sprintf('Imported %d students. Skipped %d rows.', $created, $skipped)
            );
        }

        $message = sprintf('Imported %d students. Skipped %d rows.', $created, $skipped);
        if ($errorRows > count($errors)) {
            $message .= ' Some errors were omitted.';
        }

        return redirect()
            ->route('admin.users.students.bulk')
            ->with([
                'status' => $created > 0 ? 'success' : 'error',
                'message' => $message,
                'bulk_errors' => $errors,
                'bulk_created' => $createdRows,
            ]);
    }

    public function showStudent(User $user, StudentProgressService $progressService): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);

        $progress = $progressService->build($user);

        return view('admin.users.students.show', [
            'student' => $user->load(['studentProfile', 'schoolClass']),
            ...$progress,
        ]);
    }

    public function createTeacher(): View
    {
        return view('admin.users.teachers.create', [
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function storeTeacher(StoreTeacherRequest $request): RedirectResponse
    {
        $autoGenerate = $request->boolean('auto_generate');
        $plainPassword = $autoGenerate ? Str::random(12) : $request->string('password')->toString();
        $classId = $request->integer('school_class_id');

        $teacher = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->string('phone')->toString(),
            'role' => User::ROLE_TEACHER,
            'school_class_id' => $classId ?: null,
            'password' => Hash::make($plainPassword),
        ]);

        return redirect()
            ->route('admin.users.teachers.created', $teacher)
            ->with([
                'generated_password' => $autoGenerate ? $plainPassword : null,
                'password_mode' => $autoGenerate ? 'auto' : 'manual',
            ]);
    }

    public function createdTeacher(User $user): View
    {
        abort_unless($user->role === User::ROLE_TEACHER, 404);

        return view('admin.users.teachers.created', [
            'teacher' => $user,
            'generatedPassword' => session('generated_password'),
            'passwordMode' => session('password_mode', 'manual'),
        ]);
    }

    public function editTeacher(User $user): View
    {
        abort_unless($user->role === User::ROLE_TEACHER, 404);

        return view('admin.users.teachers.edit', [
            'teacher' => $user->load('schoolClass'),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function updateTeacher(UpdateTeacherRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_TEACHER, 404);

        $data = $request->validated();
        $password = $request->string('password')->toString();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'school_class_id' => (int) $data['school_class_id'],
        ]);

        if ($password !== '') {
            $user->password = Hash::make($password);
        }

        $user->save();

        return redirect()
            ->route('admin.users.teachers.index')
            ->with([
                'status' => 'success',
                'message' => 'Teacher updated successfully.',
            ]);
    }

    public function storeStudent(StoreStudentRequest $request): RedirectResponse
    {
        $autoGenerate = $request->boolean('auto_generate');
        $plainPassword = $autoGenerate ? Str::random(12) : $request->string('password')->toString();
        $classId = $request->integer('school_class_id');
        $selectedClass = $classId ? SchoolClass::find($classId) : null;
        $profileData = array_filter([
            'class' => $selectedClass?->name,
            'department' => $request->input('department'),
            'admission_id' => $request->input('admission_id'),
        ], static fn ($value) => $value !== null && $value !== '');

        $student = DB::transaction(function () use ($request, $plainPassword, $profileData, $classId) {
            $student = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'phone' => $request->string('phone')->toString(),
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
            ->route('admin.users.students.created', $student)
            ->with([
                'generated_password' => $autoGenerate ? $plainPassword : null,
                'password_mode' => $autoGenerate ? 'auto' : 'manual',
            ]);
    }

    public function createdStudent(User $user): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);

        return view('admin.users.students.created', [
            'student' => $user,
            'profile' => $user->studentProfile,
            'generatedPassword' => session('generated_password'),
            'passwordMode' => session('password_mode', 'manual'),
        ]);
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin && $admin->role === User::ROLE_ADMIN, 403);

        if (!in_array($user->role, [User::ROLE_TEACHER, User::ROLE_STUDENT], true)) {
            abort(404);
        }

        if ($user->is_active === false) {
            return back()->with([
                'status' => 'error',
                'message' => 'This account is disabled and cannot be accessed.',
            ]);
        }

        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonator_name', $admin->name);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonator_name', $admin->name);

        return redirect()->route('dashboard.' . $user->role)->with([
            'status' => 'success',
            'message' => 'You are now logged in as ' . $user->name . '.',
        ]);
    }

    public function stopImpersonation(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');
        if (!$impersonatorId) {
            return redirect()->route('landing');
        }

        $admin = User::find($impersonatorId);
        $request->session()->forget(['impersonator_id', 'impersonator_name']);

        if (!$admin || $admin->role !== User::ROLE_ADMIN) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('landing');
        }

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard.admin')->with([
            'status' => 'success',
            'message' => 'Impersonation ended.',
        ]);
    }

    public function editStudent(User $user): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);

        return view('admin.users.students.edit', [
            'student' => $user->load(['studentProfile', 'schoolClass']),
            'classes' => SchoolClass::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function progressStudent(User $user, StudentProgressService $progressService): View
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);

        $progress = $progressService->build($user);

        return view('admin.users.students.progress', [
            'student' => $user->load(['studentProfile', 'schoolClass']),
            ...$progress,
        ]);
    }

    public function updateStudent(UpdateStudentRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_STUDENT, 404);

        $data = $request->validated();
        $password = $request->string('password')->toString();
        $classId = (int) $data['school_class_id'];
        $selectedClass = SchoolClass::find($classId);

        $student = DB::transaction(function () use ($user, $data, $password, $classId, $selectedClass) {
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

            return $user;
        });

        return redirect()
            ->route('admin.users.students.index')
            ->with([
                'status' => 'success',
                'message' => 'Student updated successfully.',
            ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->assertManageable($user);

        if ($user->id === auth()->id()) {
            return back()->with([
                'status' => 'error',
                'message' => 'You cannot disable your own account.',
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $action = $user->is_active ? 'user_enable' : 'user_disable';
        $message = sprintf('%s %s account (%s).', $user->is_active ? 'Enabled' : 'Disabled', $user->role, $user->email);

        $this->logAdminAction($action, 'success', $message);

        return back()->with([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->assertManageable($user);

        $plainPassword = Str::random(12);
        $user->password = Hash::make($plainPassword);
        $user->save();

        $message = sprintf('Password reset for %s account (%s).', $user->role, $user->email);
        $this->logAdminAction('user_reset_password', 'success', $message);

        return redirect()
            ->route('admin.users.password-reset', $user)
            ->with([
                'generated_password' => $plainPassword,
                'status' => 'success',
                'message' => $message,
            ]);
    }

    public function showPasswordReset(User $user)
    {
        $this->assertManageable($user);

        $generatedPassword = session('generated_password');
        if (!$generatedPassword) {
            return redirect()
                ->route($this->indexRouteFor($user))
                ->with([
                    'status' => 'error',
                    'message' => 'The reset password is no longer available. Please reset again if needed.',
                ]);
        }

        return view('admin.users.password-reset', [
            'user' => $user,
            'generatedPassword' => $generatedPassword,
            'backRoute' => $this->indexRouteFor($user),
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->assertManageable($user);

        if ($user->id === auth()->id()) {
            return back()->with([
                'status' => 'error',
                'message' => 'You cannot delete your own account.',
            ]);
        }

        $email = $user->email;
        $role = $user->role;
        $user->delete();

        $message = sprintf('Deleted %s account (%s).', $role, $email);
        $this->logAdminAction('user_delete', 'success', $message);

        return back()->with([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    private function assertManageable(User $user): void
    {
        abort_unless(in_array($user->role, self::MANAGED_ROLES, true), 404);
    }

    private function indexRouteFor(User $user): string
    {
        return $user->role === User::ROLE_TEACHER
            ? 'admin.users.teachers.index'
            : 'admin.users.students.index';
    }

    private function logAdminAction(string $action, string $result, string $message): void
    {
        AdminActionLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'result' => $result,
            'message' => $message,
        ]);
    }

    private function mapStudentCsvHeaders(array $headers): array
    {
        $aliases = [
            'name' => ['name', 'full_name', 'student_name'],
            'email' => ['email', 'email_address'],
            'phone' => ['phone', 'phone_number', 'mobile'],
            'school_class_id' => ['school_class_id', 'class_id'],
            'school_class' => ['school_class', 'class', 'class_name'],
            'department' => ['department', 'dept'],
            'admission_id' => ['admission_id', 'admission', 'admission_no', 'admission_number'],
            'password' => ['password'],
            'auto_generate' => ['auto_generate', 'autogenerate', 'auto_password', 'auto'],
        ];

        $map = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeCsvHeader((string) $header);
            foreach ($aliases as $canonical => $keys) {
                if (in_array($normalized, $keys, true)) {
                    $map[$canonical] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    private function normalizeCsvHeader(string $header): string
    {
        $header = ltrim($header, "\xEF\xBB\xBF");
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim($header ?? '', '_');
    }

    private function csvValue(array $row, array $headerMap, string $key): string
    {
        if (!isset($headerMap[$key])) {
            return '';
        }

        return trim((string) ($row[$headerMap[$key]] ?? ''));
    }

    private function csvTruthy(string $value): bool
    {
        $value = strtolower(trim($value));

        return in_array($value, ['1', 'true', 'yes', 'y'], true);
    }

    private function isCsvRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
