@extends('student.layout')

@section('title', 'Lesson')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ $lesson->title }}</h1>
                <p class="subtext" style="color: white;">
                    {{ $lesson->topic?->subject?->name ?? __('Subject') }} · {{ $lesson->topic?->title ?? __('Topic') }}
                </p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('student.lessons.index') }}">{{ __('Back to Lessons') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Lesson Content') }}</h4>
                @if ($isCompleted ?? false)
                    <span class="badge green"><i class="fa-solid fa-circle-check"></i> {{ __('Completed') }}</span>
                @else
                    <span class="badge gold">{{ __('Read') }}</span>
                @endif
            </div>
            <div class="panel-body">
                @if ($lesson->content)
                    <div class="lesson-content">
                        {!! nl2br(e($lesson->content)) !!}
                    </div>
                @else
                    <p class="text-muted">{{ __('No text content provided for this lesson.') }}</p>
                @endif

                @if ($lesson->file_path)
                    <div class="panel" style="margin-top: 16px;">
                        <div class="panel-header">
                            <h4>{{ __('Lesson File') }}</h4>
                            <span class="badge blue">{{ $lesson->file_type ?: 'file' }}</span>
                        </div>
                        <div class="panel-body">
                            @if ($lesson->file_type && str_starts_with($lesson->file_type, 'video'))
                                <video controls playsinline preload="metadata" style="width:100%;max-height:480px;background:#000;border-radius:6px;">
                                    <source src="{{ route('student.lessons.file', $lesson) }}" type="{{ $lesson->file_type }}">
                                    {{ __('Your browser does not support the video tag.') }}
                                </video>
                            @endif
                            <div class="table-actions" style="margin-top:8px;">
                                <a class="btn ghost btn-small" href="{{ route('student.lessons.file', $lesson) }}">{{ __('Open File') }}</a>
                                <a class="btn ghost btn-small" href="{{ route('student.lessons.file', $lesson) }}" download>{{ __('Download') }}</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        @if ($assignments->isNotEmpty())
            <section class="panel" style="margin-top:24px;">
                <div class="panel-header">
                    <h4>{{ __('Assignments for this Lesson') }}</h4>
                    <span class="badge blue">{{ $assignments->count() }}</span>
                </div>
                <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;">
                    @foreach ($assignments as $assignment)
                        <a class="lesson-related-item" href="{{ route('student.assignments.show', $assignment) }}">
                            <div class="lesson-related-icon ic-coral">
                                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                            </div>
                            <div class="lesson-related-text">
                                <div class="lesson-related-title">{{ $assignment->title }}</div>
                                @if ($assignment->due_at)
                                    <div class="lesson-related-meta">Due: {{ $assignment->due_at->format('M j, Y') }}</div>
                                @endif
                            </div>
                            <span class="lesson-related-arrow">→</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($quizzes->isNotEmpty())
            <section class="panel" style="margin-top:24px;">
                <div class="panel-header">
                    <h4>{{ __('Quizzes for this Topic') }}</h4>
                    <span class="badge blue">{{ $quizzes->count() }}</span>
                </div>
                <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;">
                    @foreach ($quizzes as $quiz)
                        <div class="lesson-related-item">
                            <div class="lesson-related-icon ic-violet">
                                <i class="fa-solid fa-clipboard-question" aria-hidden="true"></i>
                            </div>
                            <div class="lesson-related-text">
                                <div class="lesson-related-title">{{ $quiz->title }}</div>
                                @if ($quiz->time_limit_minutes)
                                    <div class="lesson-related-meta">{{ $quiz->time_limit_minutes }} min</div>
                                @endif
                            </div>
                            <form method="post" action="{{ route('student.quizzes.start', $quiz) }}" style="margin-left:auto;">
                                @csrf
                                <button class="btn ghost btn-small" type="submit">{{ __('Take Quiz') }} &rarr;</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection
