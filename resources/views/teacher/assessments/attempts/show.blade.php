@extends('teacher.layout')

@section('title', 'Attempt Review')

@section('main')
    @php($routePrefix = $type === 'exam' ? 'teacher.exams' : 'teacher.quizzes')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ ucfirst($type) }}</p>
                <h1>{{ $attempt->user?->name ?? 'Student' }} - {{ $assessment->title }}</h1>
                <p class="subtext">Attempt review and answers.</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route($routePrefix . '.attempts.index', ['assessment_id' => $assessment->id]) }}">{{ __('Back to Results') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Attempt Summary') }}</h4>
                <span class="badge {{ $attempt->status === 'completed' ? 'gold' : 'blue' }}">
                    {{ $attempt->status === 'completed' ? 'Completed' : 'In Progress' }}
                </span>
            </div>
            <div class="panel-body">
                <div class="item-list" style="display:flex;flex-direction:column;gap:6px;">
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Score') }}</p>
                            <span>{{ $attempt->score ?? 0 }} / {{ $attempt->total ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Result') }}</p>
                            @php($isPassed = ($attempt->score ?? 0) >= ($assessment->pass_mark ?? 0))
                            <span>{{ $attempt->status === 'completed' ? ($isPassed ? 'Passed' : 'Needs Review') : 'In Progress' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Pass Mark') }}</p>
                            <span>{{ $assessment->pass_mark ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Status') }}</p>
                            <span>{{ $attempt->status }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Completed') }}</p>
                            <span>{{ $attempt->completed_at?->format('Y-m-d H:i') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Answers') }}</h4>
                <span class="badge blue">{{ $questions->count() }} Questions</span>
            </div>
            <div class="panel-body">
                <div style="display:flex;flex-direction:column;gap:16px;">
                    @foreach ($questions as $index => $question)
                        @php($answer = $answers->get($question->id))
                        @php($correct = $question->options->firstWhere('is_correct', true))
                        <div class="panel">
                            <div class="panel-header">
                                <h4>Question {{ $index + 1 }}</h4>
                                <span class="badge {{ $answer?->is_correct ? 'green' : 'rose' }}">
                                    {{ $answer?->is_correct ? 'Correct' : 'Incorrect' }}
                                </span>
                            </div>
                            <div class="panel-body">
                                <p style="font-weight:600;margin-bottom:8px;">{{ $question->prompt }}</p>
                                <div style="display:flex;flex-direction:column;gap:6px;">
                                    @foreach ($question->options->sortBy('order') as $option)
                                        @php($isSelected = $answer?->assessment_option_id === $option->id)
                                        <div class="item" style="border:1px solid #e0e7f5;border-radius:10px;padding:8px;">
                                            <div class="item-info">
                                                <p>{{ $option->option_text }}</p>
                                                <span>
                                                    @if ($isSelected)
                                                        {{ __('Selected') }}
                                                    @elseif ($correct?->id === $option->id)
                                                        {{ __('Correct') }}
                                                    @else
                                                        -
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection

