<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function show(Request $request): View
    {
        $student = $request->user();
        $profile  = $student->studentProfile ?? new StudentProfile(['user_id' => $student->id]);
        $schoolClass = $student->school_class_id
            ? SchoolClass::with('section')->find($student->school_class_id)
            : null;

        return view('student.profile.show', compact('student', 'profile', 'schoolClass'));
    }

    public function update(Request $request): RedirectResponse
    {
        $student = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $student->update([
            'name'  => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return back()->with('success', 'Profile updated.');
    }
}
