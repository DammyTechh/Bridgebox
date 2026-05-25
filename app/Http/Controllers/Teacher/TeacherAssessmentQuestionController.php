<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAssessmentQuestionController extends Controller
{
    public function index(Assessment $assessment, string $type): View
    {
        $this->assertType($type, $assessment);

        $questions = $assessment->questions()
            ->with('options')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return view('teacher.assessments.questions.index', [
            'assessment' => $assessment,
            'questions' => $questions,
            'type' => $type,
        ]);
    }

    public function create(Assessment $assessment, string $type): View
    {
        $this->assertType($type, $assessment);

        return view('teacher.assessments.questions.create', [
            'assessment' => $assessment,
            'type' => $type,
        ]);
    }

    public function store(Request $request, Assessment $assessment, string $type): RedirectResponse
    {
        $this->assertType($type, $assessment);

        $data = $request->validate([
            'prompt' => 'required|string',
            'option_a' => 'required|string|max:255',
            'option_b' => 'required|string|max:255',
            'option_c' => 'required|string|max:255',
            'option_d' => 'required|string|max:255',
            'correct_option' => 'required|in:a,b,c,d',
        ]);

        $order = (int) $assessment->questions()->max('order');
        $question = $assessment->questions()->create([
            'prompt' => $data['prompt'],
            'order' => $order + 1,
            'points' => 1,
        ]);

        $options = [
            ['key' => 'a', 'text' => $data['option_a']],
            ['key' => 'b', 'text' => $data['option_b']],
            ['key' => 'c', 'text' => $data['option_c']],
            ['key' => 'd', 'text' => $data['option_d']],
        ];

        foreach ($options as $index => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $option['key'] === $data['correct_option'],
                'order' => $index + 1,
            ]);
        }

        return redirect()
            ->route($this->routePrefix($type) . '.questions.index', $assessment)
            ->with([
                'status' => 'success',
                'message' => 'Question added.',
            ]);
    }

    public function edit(Assessment $assessment, AssessmentQuestion $question, string $type): View
    {
        $this->assertType($type, $assessment, $question);

        $question->load('options');
        $options = $question->options->sortBy('order')->values();

        return view('teacher.assessments.questions.edit', [
            'assessment' => $assessment,
            'question' => $question,
            'options' => $options,
            'type' => $type,
        ]);
    }

    public function update(Request $request, Assessment $assessment, AssessmentQuestion $question, string $type): RedirectResponse
    {
        $this->assertType($type, $assessment, $question);

        $data = $request->validate([
            'prompt' => 'required|string',
            'option_a' => 'required|string|max:255',
            'option_b' => 'required|string|max:255',
            'option_c' => 'required|string|max:255',
            'option_d' => 'required|string|max:255',
            'correct_option' => 'required|in:a,b,c,d',
        ]);

        $question->update([
            'prompt' => $data['prompt'],
        ]);

        $question->options()->delete();

        $options = [
            ['key' => 'a', 'text' => $data['option_a']],
            ['key' => 'b', 'text' => $data['option_b']],
            ['key' => 'c', 'text' => $data['option_c']],
            ['key' => 'd', 'text' => $data['option_d']],
        ];

        foreach ($options as $index => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $option['key'] === $data['correct_option'],
                'order' => $index + 1,
            ]);
        }

        return redirect()
            ->route($this->routePrefix($type) . '.questions.index', $assessment)
            ->with([
                'status' => 'success',
                'message' => 'Question updated.',
            ]);
    }

    public function destroy(Assessment $assessment, AssessmentQuestion $question, string $type): RedirectResponse
    {
        $this->assertType($type, $assessment, $question);

        $question->delete();

        return back()->with([
            'status' => 'success',
            'message' => 'Question deleted.',
        ]);
    }

    private function assertType(string $type, Assessment $assessment, ?AssessmentQuestion $question = null): void
    {
        if (!in_array($type, [Assessment::TYPE_QUIZ, Assessment::TYPE_EXAM], true)) {
            abort(404);
        }

        if ($assessment->type !== $type) {
            abort(404);
        }

        if ($question && $question->assessment_id !== $assessment->id) {
            abort(404);
        }
    }

    private function routePrefix(string $type): string
    {
        return $type === Assessment::TYPE_EXAM ? 'teacher.exams' : 'teacher.quizzes';
    }
}
