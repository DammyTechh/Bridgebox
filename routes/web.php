<?php

use App\Http\Controllers\Admin\AdminActionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAssessmentAttemptController;
use App\Http\Controllers\Admin\AdminDepartmentController;
use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSubjectController;
use App\Http\Controllers\Admin\AdminTopicController;
use App\Http\Controllers\Admin\AdminClassController;
use App\Http\Controllers\Admin\AdminTopicLessonController;
use App\Http\Controllers\Admin\AdminAssessmentController;
use App\Http\Controllers\Admin\AdminAssessmentQuestionController;
use App\Http\Controllers\Teacher\TeacherAssessmentController;
use App\Http\Controllers\Teacher\TeacherAssessmentQuestionController;
use App\Http\Controllers\Teacher\TeacherAssessmentAttemptController;
use App\Http\Controllers\Teacher\TeacherAssignmentController;
use App\Http\Controllers\Teacher\TeacherAssignmentSubmissionController;
use App\Http\Controllers\Teacher\TeacherClassController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherDepartmentController;
use App\Http\Controllers\Teacher\TeacherExportController;
use App\Http\Controllers\Teacher\TeacherStudentController;
use App\Http\Controllers\Teacher\TeacherSubjectController;
use App\Http\Controllers\Teacher\TeacherLessonController;
use App\Http\Controllers\Teacher\TeacherTopicController;
use App\Http\Controllers\Teacher\TeacherTopicLessonController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentAssessmentController;
use App\Http\Controllers\Student\StudentLessonController;
use App\Http\Controllers\Student\StudentProgressController;
use App\Http\Controllers\Student\StudentSubjectController;
use App\Http\Controllers\Student\StudentTopicController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Admin\AdminAssignmentController;
use App\Http\Controllers\Admin\AdminAssignmentSubmissionController;
use App\Http\Controllers\LocaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

// Public course browser (generic mode only - controller redirects if not generic)
Route::prefix('courses')->name('courses.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Courses\CoursesController::class, 'index'])->name('index');
    Route::get('/{subject}', [\App\Http\Controllers\Courses\CoursesController::class, 'show'])->name('show');
    Route::get('/{subject}/{topic}/{lesson}', [\App\Http\Controllers\Courses\CoursesController::class, 'lesson'])->name('lesson');
    Route::get('/{subject}/image', [\App\Http\Controllers\Courses\CoursesController::class, 'image'])->name('subject.image');
    Route::get('/{subject}/{topic}/{lesson}/file', [\App\Http\Controllers\Courses\CoursesController::class, 'file'])->name('lesson.file');
    Route::get('/{subject}/{topic}/{lesson}/download', [\App\Http\Controllers\Courses\CoursesController::class, 'download'])->name('lesson.download');
});

// Mode switch (admin only — changes school ↔ generic without re-installing)
Route::post('/switch-mode', [\App\Http\Controllers\SwitchModeController::class, 'switch'])
    ->middleware('role:admin')
    ->name('switch.mode');

Route::get('/install', [\App\Http\Controllers\InstallController::class, 'show'])->name('install.show');
Route::get('/install/school', [\App\Http\Controllers\InstallController::class, 'showSchool'])->name('install.school.show');
Route::post('/install', [\App\Http\Controllers\InstallController::class, 'store'])->name('install.store');
Route::post('/install/generic', [\App\Http\Controllers\InstallController::class, 'storeGeneric'])->name('install.generic.store');
Route::get('/install/generic', [\App\Http\Controllers\InstallController::class, 'showGeneric'])->name('install.generic.show');

Route::get('/login/{role}', [AuthController::class, 'showLogin'])
    ->whereIn('role', ['admin', 'teacher', 'student'])
    ->name('login');

Route::post('/login/{role}', [AuthController::class, 'login'])
    ->whereIn('role', ['admin', 'teacher', 'student'])
    ->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update')->middleware('auth');
Route::post('/impersonate/stop', [AdminUserController::class, 'stopImpersonation'])->name('impersonate.stop');

Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])
    ->middleware('role:admin')
    ->name('dashboard.admin');

Route::get('/dashboard/admin/logs', [AdminLogController::class, 'index'])
    ->middleware('role:admin')
    ->name('admin.logs.index');

Route::get('/dashboard/admin/status', [AdminDashboardController::class, 'status'])
    ->middleware('role:admin')
    ->name('dashboard.admin.status');

Route::get('/dashboard/admin/settings', function (Request $request) {
    $path = storage_path('app/hotspot.json');
    if (file_exists($path)) {
        $data = json_decode(file_get_contents($path), true) ?: [];
        return response()->json($data);
    }
    return response()->json(['ssid' => '', 'password' => '']);
})->middleware('role:admin')->name('dashboard.admin.settings.get');

Route::post('/dashboard/admin/actions/{action}', [AdminActionController::class, 'run'])
    ->middleware('role:admin')
    ->name('dashboard.admin.actions');

Route::post('/dashboard/admin/settings', function (Request $request) {
    $request->validate([
        'hotspot_ssid' => 'required|string|max:64',
        'hotspot_password' => 'nullable|string|max:128',
    ]);
    $path = storage_path('app');
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    $data = [
        'ssid' => $request->input('hotspot_ssid'),
        'password' => $request->input('hotspot_password'),
    ];
    file_put_contents($path . '/hotspot.json', json_encode($data));
    return redirect()->back()->with('action_status', 'success')->with('action_message', 'Hotspot settings saved.');
})->middleware('role:admin')->name('dashboard.admin.settings');

Route::prefix('dashboard/admin/users')
    ->middleware('role:admin')
    ->name('admin.users.')
    ->group(function () {
        Route::get('/teachers', [AdminUserController::class, 'teachers'])->name('teachers.index');
        Route::get('/teachers/create', [AdminUserController::class, 'createTeacher'])->name('teachers.create');
        Route::post('/teachers', [AdminUserController::class, 'storeTeacher'])->name('teachers.store');
        Route::get('/teachers/{user}/edit', [AdminUserController::class, 'editTeacher'])->name('teachers.edit');
        Route::put('/teachers/{user}', [AdminUserController::class, 'updateTeacher'])->name('teachers.update');
        Route::get('/teachers/{user}/created', [AdminUserController::class, 'createdTeacher'])->name('teachers.created');
        Route::get('/students', [AdminUserController::class, 'students'])->name('students.index');
        Route::get('/students/create', [AdminUserController::class, 'createStudent'])->name('students.create');
        Route::get('/students/bulk', [AdminUserController::class, 'bulkStudents'])->name('students.bulk');
        Route::post('/students/bulk', [AdminUserController::class, 'storeBulkStudents'])->name('students.bulk.store');
        Route::get('/students/{user}', [AdminUserController::class, 'showStudent'])->name('students.show');
        Route::post('/students', [AdminUserController::class, 'storeStudent'])->name('students.store');
        Route::get('/students/{user}/edit', [AdminUserController::class, 'editStudent'])->name('students.edit');
        Route::put('/students/{user}', [AdminUserController::class, 'updateStudent'])->name('students.update');
        Route::get('/students/{user}/created', [AdminUserController::class, 'createdStudent'])->name('students.created');
        Route::get('/students/{user}/progress', [AdminUserController::class, 'progressStudent'])->name('students.progress');
        Route::post('/users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('impersonate');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggleStatus'])->name('toggle');
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('reset');
        Route::get('/users/{user}/password-reset', [AdminUserController::class, 'showPasswordReset'])->name('password-reset');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('delete');
    });

Route::prefix('dashboard/admin/classes')
    ->middleware('role:admin')
    ->name('admin.classes.')
    ->group(function () {
        Route::get('/', [AdminClassController::class, 'index'])->name('index');
        Route::get('/create', [AdminClassController::class, 'create'])->name('create');
        Route::post('/', [AdminClassController::class, 'store'])->name('store');
        Route::get('/{class}/edit', [AdminClassController::class, 'edit'])->name('edit');
        Route::put('/{class}', [AdminClassController::class, 'update'])->name('update');
        Route::delete('/{class}', [AdminClassController::class, 'destroy'])->name('delete');
    });

Route::prefix('dashboard/admin/subjects')
    ->middleware('role:admin')
    ->name('admin.subjects.')
    ->group(function () {
        Route::get('/by-class', [AdminSubjectController::class, 'byClass'])->name('by-class');
        Route::get('/', [AdminSubjectController::class, 'index'])->name('index');
        Route::get('/create', [AdminSubjectController::class, 'create'])->name('create');
        Route::post('/', [AdminSubjectController::class, 'store'])->name('store');
        Route::get('/{subject}/edit', [AdminSubjectController::class, 'edit'])->name('edit');
        Route::put('/{subject}', [AdminSubjectController::class, 'update'])->name('update');
        Route::delete('/{subject}', [AdminSubjectController::class, 'destroy'])->name('delete');
    });

Route::prefix('dashboard/admin/departments')
    ->middleware('role:admin')
    ->name('admin.departments.')
    ->group(function () {
        Route::get('/', [AdminDepartmentController::class, 'index'])->name('index');
        Route::get('/create', [AdminDepartmentController::class, 'create'])->name('create');
        Route::post('/', [AdminDepartmentController::class, 'store'])->name('store');
        Route::get('/{department}/edit', [AdminDepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [AdminDepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [AdminDepartmentController::class, 'destroy'])->name('delete');
    });

Route::prefix('dashboard/admin/topics')
    ->middleware('role:admin')
    ->name('admin.topics.')
    ->group(function () {
        Route::get('/', [AdminTopicController::class, 'index'])->name('index');
        Route::get('/by-subject', [AdminTopicController::class, 'bySubject'])->name('by-subject');
        Route::get('/lessons/by-topic', [AdminTopicLessonController::class, 'byTopic'])->name('lessons.by-topic');
        Route::get('/create', [AdminTopicController::class, 'create'])->name('create');
        Route::post('/', [AdminTopicController::class, 'store'])->name('store');
        Route::get('/{topic}/edit', [AdminTopicController::class, 'edit'])->name('edit');
        Route::put('/{topic}', [AdminTopicController::class, 'update'])->name('update');
        Route::delete('/{topic}', [AdminTopicController::class, 'destroy'])->name('delete');

        Route::get('/{topic}/lessons', [AdminTopicLessonController::class, 'index'])->name('lessons.index');
        Route::get('/{topic}/lessons/create', [AdminTopicLessonController::class, 'create'])->name('lessons.create');
        Route::post('/{topic}/lessons', [AdminTopicLessonController::class, 'store'])->name('lessons.store');
        Route::get('/{topic}/lessons/{lesson}', [AdminTopicLessonController::class, 'show'])->name('lessons.show');
        Route::get('/{topic}/lessons/{lesson}/edit', [AdminTopicLessonController::class, 'edit'])->name('lessons.edit');
        Route::put('/{topic}/lessons/{lesson}', [AdminTopicLessonController::class, 'update'])->name('lessons.update');
        Route::get('/{topic}/lessons/{lesson}/file', [AdminTopicLessonController::class, 'file'])->name('lessons.file');
        Route::get('/{topic}/lessons/{lesson}/download', [AdminTopicLessonController::class, 'download'])->name('lessons.download');
        Route::delete('/{topic}/lessons/{lesson}', [AdminTopicLessonController::class, 'destroy'])->name('lessons.delete');
    });

Route::prefix('dashboard/admin/quizzes')
    ->middleware('role:admin')
    ->name('admin.quizzes.')
    ->group(function () {
        Route::get('/export', [AdminExportController::class, 'assessments'])->defaults('type', 'quiz')->name('export');
        Route::get('/', [AdminAssessmentController::class, 'index'])->defaults('type', 'quiz')->name('index');
        Route::get('/create', [AdminAssessmentController::class, 'create'])->defaults('type', 'quiz')->name('create');
        Route::post('/', [AdminAssessmentController::class, 'store'])->defaults('type', 'quiz')->name('store');
        Route::get('/{assessment}/edit', [AdminAssessmentController::class, 'edit'])->defaults('type', 'quiz')->name('edit');
        Route::put('/{assessment}', [AdminAssessmentController::class, 'update'])->defaults('type', 'quiz')->name('update');
        Route::delete('/{assessment}', [AdminAssessmentController::class, 'destroy'])->defaults('type', 'quiz')->name('delete');
        Route::get('/attempts', [AdminAssessmentAttemptController::class, 'index'])->defaults('type', 'quiz')->name('attempts.index');
        Route::get('/attempts/{attempt}', [AdminAssessmentAttemptController::class, 'show'])->defaults('type', 'quiz')->name('attempts.show');

        Route::get('/{assessment}/questions', [AdminAssessmentQuestionController::class, 'index'])->defaults('type', 'quiz')->name('questions.index');
        Route::get('/{assessment}/questions/create', [AdminAssessmentQuestionController::class, 'create'])->defaults('type', 'quiz')->name('questions.create');
        Route::post('/{assessment}/questions', [AdminAssessmentQuestionController::class, 'store'])->defaults('type', 'quiz')->name('questions.store');
        Route::get('/{assessment}/questions/{question}/edit', [AdminAssessmentQuestionController::class, 'edit'])->defaults('type', 'quiz')->name('questions.edit');
        Route::put('/{assessment}/questions/{question}', [AdminAssessmentQuestionController::class, 'update'])->defaults('type', 'quiz')->name('questions.update');
        Route::delete('/{assessment}/questions/{question}', [AdminAssessmentQuestionController::class, 'destroy'])->defaults('type', 'quiz')->name('questions.delete');
    });

Route::prefix('dashboard/admin/exams')
    ->middleware('role:admin')
    ->name('admin.exams.')
    ->group(function () {
        Route::get('/export', [AdminExportController::class, 'assessments'])->defaults('type', 'exam')->name('export');
        Route::get('/', [AdminAssessmentController::class, 'index'])->defaults('type', 'exam')->name('index');
        Route::get('/create', [AdminAssessmentController::class, 'create'])->defaults('type', 'exam')->name('create');
        Route::post('/', [AdminAssessmentController::class, 'store'])->defaults('type', 'exam')->name('store');
        Route::get('/{assessment}/edit', [AdminAssessmentController::class, 'edit'])->defaults('type', 'exam')->name('edit');
        Route::put('/{assessment}', [AdminAssessmentController::class, 'update'])->defaults('type', 'exam')->name('update');
        Route::delete('/{assessment}', [AdminAssessmentController::class, 'destroy'])->defaults('type', 'exam')->name('delete');
        Route::get('/attempts', [AdminAssessmentAttemptController::class, 'index'])->defaults('type', 'exam')->name('attempts.index');
        Route::get('/attempts/{attempt}', [AdminAssessmentAttemptController::class, 'show'])->defaults('type', 'exam')->name('attempts.show');

        Route::get('/{assessment}/questions', [AdminAssessmentQuestionController::class, 'index'])->defaults('type', 'exam')->name('questions.index');
        Route::get('/{assessment}/questions/create', [AdminAssessmentQuestionController::class, 'create'])->defaults('type', 'exam')->name('questions.create');
        Route::post('/{assessment}/questions', [AdminAssessmentQuestionController::class, 'store'])->defaults('type', 'exam')->name('questions.store');
        Route::get('/{assessment}/questions/{question}/edit', [AdminAssessmentQuestionController::class, 'edit'])->defaults('type', 'exam')->name('questions.edit');
        Route::put('/{assessment}/questions/{question}', [AdminAssessmentQuestionController::class, 'update'])->defaults('type', 'exam')->name('questions.update');
        Route::delete('/{assessment}/questions/{question}', [AdminAssessmentQuestionController::class, 'destroy'])->defaults('type', 'exam')->name('questions.delete');
    });

Route::prefix('dashboard/admin/assignments')
    ->middleware('role:admin')
    ->name('admin.assignments.')
    ->group(function () {
        Route::get('/export', [AdminExportController::class, 'assignments'])->name('export');
        Route::get('/', [AdminAssignmentController::class, 'index'])->name('index');
        Route::get('/create', [AdminAssignmentController::class, 'create'])->name('create');
        Route::post('/', [AdminAssignmentController::class, 'store'])->name('store');
        Route::get('/{assignment}/edit', [AdminAssignmentController::class, 'edit'])->name('edit');
        Route::put('/{assignment}', [AdminAssignmentController::class, 'update'])->name('update');
        Route::delete('/{assignment}', [AdminAssignmentController::class, 'destroy'])->name('delete');

        Route::get('/{assignment}/submissions', [AdminAssignmentSubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/{assignment}/submissions/{submission}', [AdminAssignmentSubmissionController::class, 'show'])->name('submissions.show');
        Route::put('/{assignment}/submissions/{submission}', [AdminAssignmentSubmissionController::class, 'update'])->name('submissions.update');
        Route::get('/{assignment}/submissions/{submission}/download', [AdminAssignmentSubmissionController::class, 'download'])->name('submissions.download');
        Route::delete('/{assignment}/submissions/{submission}', [AdminAssignmentSubmissionController::class, 'destroy'])->name('submissions.delete');
    });

Route::prefix('dashboard/student/assignments')
    ->middleware('role:student')
    ->name('student.assignments.')
    ->group(function () {
        Route::get('/', [StudentAssignmentController::class, 'index'])->name('index');
        Route::get('/{assignment}', [StudentAssignmentController::class, 'show'])->name('show');
        Route::post('/{assignment}', [StudentAssignmentController::class, 'submit'])->name('submit');
    });

Route::prefix('dashboard/student')
    ->middleware('role:student')
    ->name('student.')
    ->group(function () {
        Route::get('/subjects', [StudentSubjectController::class, 'index'])->name('subjects.index');
        Route::get('/topics', [StudentTopicController::class, 'index'])->name('topics.index');
        Route::get('/topics/by-subject', [StudentTopicController::class, 'bySubject'])->name('topics.by-subject');
        Route::get('/lessons', [StudentLessonController::class, 'index'])->name('lessons.index');
        Route::get('/lessons/{lesson}', [StudentLessonController::class, 'show'])->name('lessons.show');
        Route::get('/lessons/{lesson}/file', [StudentLessonController::class, 'file'])->name('lessons.file');
        Route::get('/progress', [StudentProgressController::class, 'index'])->name('progress.index');
        Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
    });

Route::prefix('dashboard/student/quizzes')
    ->middleware('role:student')
    ->name('student.quizzes.')
    ->group(function () {
        Route::get('/', [StudentAssessmentController::class, 'index'])->defaults('type', 'quiz')->name('index');
        Route::post('/{assessment}/start', [StudentAssessmentController::class, 'start'])->defaults('type', 'quiz')->name('start');
        Route::get('/attempts/{attempt}', [StudentAssessmentController::class, 'attempt'])->defaults('type', 'quiz')->name('attempt');
        Route::post('/attempts/{attempt}/forfeit', [StudentAssessmentController::class, 'forfeit'])->defaults('type', 'quiz')->name('forfeit');
        Route::post('/attempts/{attempt}', [StudentAssessmentController::class, 'submit'])->defaults('type', 'quiz')->name('submit');
        Route::get('/attempts/{attempt}/result', [StudentAssessmentController::class, 'result'])->defaults('type', 'quiz')->name('result');
    });

Route::prefix('dashboard/student/exams')
    ->middleware('role:student')
    ->name('student.exams.')
    ->group(function () {
        Route::get('/', [StudentAssessmentController::class, 'index'])->defaults('type', 'exam')->name('index');
        Route::post('/{assessment}/start', [StudentAssessmentController::class, 'start'])->defaults('type', 'exam')->name('start');
        Route::get('/attempts/{attempt}', [StudentAssessmentController::class, 'attempt'])->defaults('type', 'exam')->name('attempt');
        Route::post('/attempts/{attempt}/forfeit', [StudentAssessmentController::class, 'forfeit'])->defaults('type', 'exam')->name('forfeit');
        Route::post('/attempts/{attempt}', [StudentAssessmentController::class, 'submit'])->defaults('type', 'exam')->name('submit');
        Route::get('/attempts/{attempt}/result', [StudentAssessmentController::class, 'result'])->defaults('type', 'exam')->name('result');
    });

Route::get('/dashboard/teacher', [TeacherDashboardController::class, 'index'])
    ->middleware('role:teacher')
    ->name('dashboard.teacher');

Route::prefix('dashboard/teacher')
    ->middleware('role:teacher')
    ->name('teacher.')
    ->group(function () {
        Route::prefix('students')->name('students.')->group(function () {
            Route::get('/', [TeacherStudentController::class, 'index'])->name('index');
            Route::get('/create', [TeacherStudentController::class, 'create'])->name('create');
            Route::post('/', [TeacherStudentController::class, 'store'])->name('store');
            Route::get('/{user}', [TeacherStudentController::class, 'show'])->name('show');
            Route::get('/{user}/created', [TeacherStudentController::class, 'created'])->name('created');
            Route::get('/{user}/progress', [TeacherStudentController::class, 'progress'])->name('progress');
            Route::get('/{user}/edit', [TeacherStudentController::class, 'edit'])->name('edit');
            Route::put('/{user}', [TeacherStudentController::class, 'update'])->name('update');
            Route::delete('/{user}', [TeacherStudentController::class, 'destroy'])->name('delete');
        });

        Route::prefix('classes')->name('classes.')->group(function () {
            Route::get('/', [TeacherClassController::class, 'index'])->name('index');
            Route::get('/create', [TeacherClassController::class, 'create'])->name('create');
            Route::post('/', [TeacherClassController::class, 'store'])->name('store');
            Route::get('/{class}/edit', [TeacherClassController::class, 'edit'])->name('edit');
            Route::put('/{class}', [TeacherClassController::class, 'update'])->name('update');
            Route::delete('/{class}', [TeacherClassController::class, 'destroy'])->name('delete');
        });

        Route::prefix('subjects')->name('subjects.')->group(function () {
            Route::get('/by-class', [TeacherSubjectController::class, 'byClass'])->name('by-class');
            Route::get('/', [TeacherSubjectController::class, 'index'])->name('index');
            Route::get('/create', [TeacherSubjectController::class, 'create'])->name('create');
            Route::post('/', [TeacherSubjectController::class, 'store'])->name('store');
            Route::get('/{subject}/edit', [TeacherSubjectController::class, 'edit'])->name('edit');
            Route::put('/{subject}', [TeacherSubjectController::class, 'update'])->name('update');
            Route::delete('/{subject}', [TeacherSubjectController::class, 'destroy'])->name('delete');
        });

        Route::prefix('departments')->name('departments.')->group(function () {
            Route::get('/', [TeacherDepartmentController::class, 'index'])->name('index');
            Route::get('/create', [TeacherDepartmentController::class, 'create'])->name('create');
            Route::post('/', [TeacherDepartmentController::class, 'store'])->name('store');
            Route::get('/{department}/edit', [TeacherDepartmentController::class, 'edit'])->name('edit');
            Route::put('/{department}', [TeacherDepartmentController::class, 'update'])->name('update');
            Route::delete('/{department}', [TeacherDepartmentController::class, 'destroy'])->name('delete');
        });

        Route::get('lessons', [TeacherLessonController::class, 'index'])->name('lessons.index');

        Route::prefix('topics')->name('topics.')->group(function () {
            Route::get('/', [TeacherTopicController::class, 'index'])->name('index');
            Route::get('/by-subject', [TeacherTopicController::class, 'bySubject'])->name('by-subject');
            Route::get('/lessons/by-topic', [TeacherTopicController::class, 'lessonsByTopic'])->name('lessons.by-topic');
            Route::get('/create', [TeacherTopicController::class, 'create'])->name('create');
            Route::post('/', [TeacherTopicController::class, 'store'])->name('store');
            Route::get('/{topic}/edit', [TeacherTopicController::class, 'edit'])->name('edit');
            Route::put('/{topic}', [TeacherTopicController::class, 'update'])->name('update');
            Route::delete('/{topic}', [TeacherTopicController::class, 'destroy'])->name('delete');

            // Lesson actions for teachers
            Route::get('/{topic}/lessons', [TeacherTopicLessonController::class, 'index'])->name('lessons.index');
            Route::get('/{topic}/lessons/create', [TeacherTopicLessonController::class, 'create'])->name('lessons.create');
            Route::post('/{topic}/lessons', [TeacherTopicLessonController::class, 'store'])->name('lessons.store');
            Route::get('/{topic}/lessons/{lesson}', [TeacherTopicLessonController::class, 'show'])->name('lessons.show');
            Route::get('/{topic}/lessons/{lesson}/edit', [TeacherTopicLessonController::class, 'edit'])->name('lessons.edit');
            Route::put('/{topic}/lessons/{lesson}', [TeacherTopicLessonController::class, 'update'])->name('lessons.update');
            Route::get('/{topic}/lessons/{lesson}/download', [TeacherTopicLessonController::class, 'download'])->name('lessons.download');
            Route::delete('/{topic}/lessons/{lesson}', [TeacherTopicLessonController::class, 'destroy'])->name('lessons.delete');
        });

        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::get('/export', [TeacherExportController::class, 'assignments'])->name('export');
            Route::get('/', [TeacherAssignmentController::class, 'index'])->name('index');
            Route::get('/create', [TeacherAssignmentController::class, 'create'])->name('create');
            Route::post('/', [TeacherAssignmentController::class, 'store'])->name('store');
            Route::get('/{assignment}/edit', [TeacherAssignmentController::class, 'edit'])->name('edit');
            Route::put('/{assignment}', [TeacherAssignmentController::class, 'update'])->name('update');
            Route::delete('/{assignment}', [TeacherAssignmentController::class, 'destroy'])->name('delete');
            Route::get('/{assignment}/submissions', [TeacherAssignmentSubmissionController::class, 'index'])->name('submissions.index');
            Route::get('/{assignment}/submissions/{submission}', [TeacherAssignmentSubmissionController::class, 'show'])->name('submissions.show');
            Route::put('/{assignment}/submissions/{submission}', [TeacherAssignmentSubmissionController::class, 'update'])->name('submissions.update');
            Route::get('/{assignment}/submissions/{submission}/download', [TeacherAssignmentSubmissionController::class, 'download'])->name('submissions.download');
            Route::delete('/{assignment}/submissions/{submission}', [TeacherAssignmentSubmissionController::class, 'destroy'])->name('submissions.delete');
        });

        Route::prefix('quizzes')->name('quizzes.')->group(function () {
            Route::get('/export', [TeacherExportController::class, 'assessments'])->defaults('type', 'quiz')->name('export');
            Route::get('/', [TeacherAssessmentController::class, 'index'])->defaults('type', 'quiz')->name('index');
            Route::get('/create', [TeacherAssessmentController::class, 'create'])->defaults('type', 'quiz')->name('create');
            Route::post('/', [TeacherAssessmentController::class, 'store'])->defaults('type', 'quiz')->name('store');
            Route::get('/{assessment}/edit', [TeacherAssessmentController::class, 'edit'])->defaults('type', 'quiz')->name('edit');
            Route::put('/{assessment}', [TeacherAssessmentController::class, 'update'])->defaults('type', 'quiz')->name('update');
            Route::delete('/{assessment}', [TeacherAssessmentController::class, 'destroy'])->defaults('type', 'quiz')->name('delete');
            Route::get('/attempts', [TeacherAssessmentAttemptController::class, 'index'])->defaults('type', 'quiz')->name('attempts.index');
            Route::get('/attempts/{attempt}', [TeacherAssessmentAttemptController::class, 'show'])->defaults('type', 'quiz')->name('attempts.show');

            Route::get('/{assessment}/questions', [TeacherAssessmentQuestionController::class, 'index'])->defaults('type', 'quiz')->name('questions.index');
            Route::get('/{assessment}/questions/create', [TeacherAssessmentQuestionController::class, 'create'])->defaults('type', 'quiz')->name('questions.create');
            Route::post('/{assessment}/questions', [TeacherAssessmentQuestionController::class, 'store'])->defaults('type', 'quiz')->name('questions.store');
            Route::get('/{assessment}/questions/{question}/edit', [TeacherAssessmentQuestionController::class, 'edit'])->defaults('type', 'quiz')->name('questions.edit');
            Route::put('/{assessment}/questions/{question}', [TeacherAssessmentQuestionController::class, 'update'])->defaults('type', 'quiz')->name('questions.update');
            Route::delete('/{assessment}/questions/{question}', [TeacherAssessmentQuestionController::class, 'destroy'])->defaults('type', 'quiz')->name('questions.delete');
        });

        Route::prefix('exams')->name('exams.')->group(function () {
            Route::get('/export', [TeacherExportController::class, 'assessments'])->defaults('type', 'exam')->name('export');
            Route::get('/', [TeacherAssessmentController::class, 'index'])->defaults('type', 'exam')->name('index');
            Route::get('/create', [TeacherAssessmentController::class, 'create'])->defaults('type', 'exam')->name('create');
            Route::post('/', [TeacherAssessmentController::class, 'store'])->defaults('type', 'exam')->name('store');
            Route::get('/{assessment}/edit', [TeacherAssessmentController::class, 'edit'])->defaults('type', 'exam')->name('edit');
            Route::put('/{assessment}', [TeacherAssessmentController::class, 'update'])->defaults('type', 'exam')->name('update');
            Route::delete('/{assessment}', [TeacherAssessmentController::class, 'destroy'])->defaults('type', 'exam')->name('delete');
            Route::get('/attempts', [TeacherAssessmentAttemptController::class, 'index'])->defaults('type', 'exam')->name('attempts.index');
            Route::get('/attempts/{attempt}', [TeacherAssessmentAttemptController::class, 'show'])->defaults('type', 'exam')->name('attempts.show');

            Route::get('/{assessment}/questions', [TeacherAssessmentQuestionController::class, 'index'])->defaults('type', 'exam')->name('questions.index');
            Route::get('/{assessment}/questions/create', [TeacherAssessmentQuestionController::class, 'create'])->defaults('type', 'exam')->name('questions.create');
            Route::post('/{assessment}/questions', [TeacherAssessmentQuestionController::class, 'store'])->defaults('type', 'exam')->name('questions.store');
            Route::get('/{assessment}/questions/{question}/edit', [TeacherAssessmentQuestionController::class, 'edit'])->defaults('type', 'exam')->name('questions.edit');
            Route::put('/{assessment}/questions/{question}', [TeacherAssessmentQuestionController::class, 'update'])->defaults('type', 'exam')->name('questions.update');
            Route::delete('/{assessment}/questions/{question}', [TeacherAssessmentQuestionController::class, 'destroy'])->defaults('type', 'exam')->name('questions.delete');
        });
    });

Route::get('/dashboard/student', [StudentDashboardController::class, 'index'])
    ->middleware('role:student')
    ->name('dashboard.student');

Route::middleware(['auth'])->prefix('usb')->name('usb.')->group(function () {
    // Read-only listing of imported content (any authenticated user, incl. students)
    Route::get('/list',     [\App\Http\Controllers\UsbImportController::class, 'index'])->name('list');

    // Drive detection + progress polling (any authenticated user; admin/teacher
    // dashboards consume this. Empty drive list is safe to expose.)
    Route::get('/drives',   [\App\Http\Controllers\UsbImportController::class, 'drives'])->name('drives');
    Route::get('/progress', [\App\Http\Controllers\UsbImportController::class, 'progress'])->name('progress');

    // Write operations — controller enforces admin/teacher role
    Route::post('/start',                  [\App\Http\Controllers\UsbImportController::class, 'start'])->name('start');
    Route::delete('/content/{content}',    [\App\Http\Controllers\UsbImportController::class, 'destroy'])->name('destroy');
});
