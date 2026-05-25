<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentSubjectController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $classId = $student?->school_class_id;
        $sectionId = $classId ? SchoolClass::whereKey($classId)->value('section_id') : null;
        $search = $request->string('q')->trim()->toString();

        $subjectIds = Topic::query()
            ->where('school_class_id', $classId)
            ->select('subject_id')
            ->distinct();

        $subjects = Subject::query()
            ->withCount(['topics' => function ($query) use ($classId) {
                $query->where('school_class_id', $classId);
            }])
            ->whereIn('id', $subjectIds)
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('student.subjects.index', [
            'student' => $student,
            'subjects' => $subjects,
            'search' => $search,
        ]);
    }
}
