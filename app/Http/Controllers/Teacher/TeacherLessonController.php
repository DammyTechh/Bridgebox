<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherLessonController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $topicId = $request->integer('topic_id');
        $subjectId = $request->integer('subject_id');
        $teacherClassId = $request->user()?->school_class_id;

        $lessons = Lesson::query()
            ->with(['topic.subject', 'topic.schoolClass'])
            ->whereHas('topic', function ($q) use ($teacherClassId) {
                $q->where('school_class_id', $teacherClassId ?: 0);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($topicId, function ($q) use ($topicId) {
                $q->where('topic_id', $topicId);
            })
            ->when($subjectId, function ($q) use ($subjectId) {
                $q->whereHas('topic', function ($topicQuery) use ($subjectId) {
                    $topicQuery->where('subject_id', $subjectId);
                });
            })
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        $topics = $teacherClassId
            ? Topic::where('school_class_id', $teacherClassId)->orderBy('title')->get()
            : collect();

        $subjectsQuery = Subject::orderBy('name');
        if ($teacherClassId) {
            $sectionId = SchoolClass::whereKey($teacherClassId)->value('section_id');
            if ($sectionId) {
                $subjectsQuery->where('section_id', $sectionId);
            }
        } else {
            $subjectsQuery->whereRaw('1 = 0');
        }

        return view('teacher.lessons.index', [
            'lessons' => $lessons,
            'topics' => $topics,
            'subjects' => $subjectsQuery->get(),
            'search' => $search,
            'selectedTopicId' => $topicId ?: null,
            'selectedSubjectId' => $subjectId ?: null,
        ]);
    }
}
