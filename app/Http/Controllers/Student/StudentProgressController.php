<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function index(Request $request, StudentProgressService $progressService): View
    {
        $student = $request->user();
        $progress = $student ? $progressService->build($student) : [];

        return view('student.progress.index', [
            'student' => $student,
            ...$progress,
        ]);
    }
}
