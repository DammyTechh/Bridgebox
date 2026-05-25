<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentTopicController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $classId = $student?->school_class_id;
        $sectionId = $classId ? SchoolClass::whereKey($classId)->value('section_id') : null;
        $search = $request->string('q')->trim()->toString();
        $subjectId = $request->integer('subject_id');

        $subjects = Subject::query()
            ->whereIn('id', Topic::query()
                ->where('school_class_id', $classId)
                ->select('subject_id')
                ->distinct())
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            ->orderBy('name')
            ->get();

        if ($subjectId && $sectionId) {
            $subjectMatches = Subject::query()
                ->whereKey($subjectId)
                ->where('section_id', $sectionId)
                ->exists();
            if (!$subjectMatches) {
                $subjectId = null;
            }
        }

        $topics = Topic::query()
            ->with(['subject', 'schoolClass'])
            ->withCount('lessons')
            ->where('school_class_id', $classId)
            ->when($subjectId, function ($query) use ($subjectId) {
                $query->where('subject_id', $subjectId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        return view('student.topics.index', [
            'student' => $student,
            'topics' => $topics,
            'subjects' => $subjects,
            'search' => $search,
            'selectedSubjectId' => $subjectId ?: null,
        ]);
    }

    public function bySubject(Request $request): JsonResponse
    {
        $subjectId = $request->integer('subject_id');
        $student = $request->user();
        $classId = $student?->school_class_id;

        if (!$subjectId || !$classId) {
            return response()->json([]);
        }

        $sectionId = SchoolClass::whereKey($classId)->value('section_id');
        if ($sectionId) {
            $subjectMatches = Subject::query()
                ->whereKey($subjectId)
                ->where('section_id', $sectionId)
                ->exists();
            if (!$subjectMatches) {
                return response()->json([]);
            }
        }

        $topics = Topic::query()
            ->where('school_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json($topics);
    }
}
