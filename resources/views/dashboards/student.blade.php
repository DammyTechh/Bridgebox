@extends('student.layout')

@section('title', __('Student Dashboard'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student Hub') }}</p>
                <h1>{{ __('Keep going,') }} {{ $student?->name ?? __('Student') }}.</h1>
                <p class="subtext">{{ __('Pick up where you left off and track your progress.') }}</p>
            </div>
            <div class="actions">
                @if ($nextLesson)
                    <a class="btn ghost" href="{{ route('student.lessons.show', $nextLesson) }}">{{ __('Resume Lesson') }}</a>
                @else
                    <a class="btn ghost" href="{{ route('student.lessons.index') }}">{{ __('View Lessons') }}</a>
                @endif
                <a class="btn primary" href="{{ route('dashboard.student') }}">{{ __('Refresh') }}</a>
            </div>
        </header>

        <section class="quick-tabs">
            <div class="tab" style="--accent: #4a7bd1; --d: 0.05s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Lessons Ready') }}</p>
                    <span>{{ $lessonsCount ?? 0 }} {{ __('available') }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #e56b6f; --d: 0.1s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Quizzes') }}</p>
                    <span>{{ $quizzesCount ?? 0 }} {{ __('available') }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #f2b84b; --d: 0.15s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Assignments') }}</p>
                    <span>{{ $assignmentsCount ?? 0 }} {{ __('available') }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #56c1a7; --d: 0.2s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Exams') }}</p>
                    <span>{{ $examsCount ?? 0 }} {{ __('available') }}</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Subjects') }}</h4>
                <span class="badge blue">{{ $subjects->count() ?? 0 }}</span>
            </div>

            @if (!($student?->school_class_id))
                <div class="alert alert-error" role="status">
                    <span data-alert-message>Your account has no class assigned. Subjects will not appear until a class is assigned.</span>
                </div>
            @endif

            @php
                $iconColors = ['ic-coral', 'ic-blue', 'ic-teal', 'ic-violet', 'ic-gold'];
            @endphp

            <div class="subject-card-grid">
                @forelse ($subjects as $i => $subject)
                    @php
                        $colorClass = $iconColors[$i % count($iconColors)];
                        $initials = collect(explode(' ', $subject->name))
                            ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <a class="subject-card" href="{{ route('student.topics.index', ['subject_id' => $subject->id]) }}">
                        @if ($subject->code)
                            <span class="subject-card-code">{{ $subject->code }}</span>
                        @endif
                        <div class="subject-card-icon {{ $colorClass }}">
                            {{ $initials }}
                        </div>
                        <div class="subject-card-body">
                            <h3 class="subject-card-name">{{ $subject->name }}</h3>
                            @if ($subject->description)
                                <p class="subject-card-desc">{{ Str::limit($subject->description, 100) }}</p>
                            @else
                                <p class="subject-card-desc text-muted">{{ $subject->topics_count ?? 0 }} {{ Str::plural('topic', $subject->topics_count ?? 0) }}</p>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-muted">{{ __('No subjects available for your class.') }}</p>
                @endforelse
            </div>

            @if ($subjects->isNotEmpty())
                <!-- <a class="btn ghost btn-small" href="{{ route('student.subjects.index') }}" style="margin-top:4px;">View all subjects</a> -->
            @endif
        </section>

        {{-- Activity Section --}}
        <section class="panel" style="margin-top:24px;">
            <div class="panel-header">
                <h4>{{ __('Activity') }}</h4>
            </div>

            {{-- Assignments --}}
            @if ($recentAssignments->isNotEmpty())
                <div class="activity-group">
                    <div class="activity-group-header">
                        <div class="activity-group-icon ic-coral">
                            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                        </div>
                        <span class="activity-group-label">{{ __('Assignments') }}</span>
                        <span class="badge blue">{{ $assignmentsCount }}</span>
                        <a class="activity-view-all" href="{{ route('student.assignments.index') }}">{{ __('View all') }} →</a>
                    </div>
                    <div class="activity-card-grid">
                        @foreach ($recentAssignments as $assignment)
                            @php($submission = $assignment->submissions->where('user_id', $student?->id)->first())
                            <a class="activity-card" href="{{ route('student.assignments.show', $assignment) }}">
                                <div class="activity-card-top">
                                    <span class="activity-card-title">{{ $assignment->title }}</span>
                                    @if ($submission)
                                        <span class="item-chip item-chip--done" style="font-size:10px;padding:3px 8px;">
                                            <i class="fa-solid fa-circle-check"></i> {{ ucfirst($submission->status) }}
                                        </span>
                                    @else
                                        <span class="item-chip" style="font-size:10px;padding:3px 8px;color:#e56b6f;background:#fde8e8;">{{ __('Pending') }}</span>
                                    @endif
                                </div>
                                <div class="activity-card-meta">
                                    <span>{{ $assignment->lesson?->topic?->subject?->name ?? '-' }}</span>
                                    @if ($assignment->due_at)
                                        <span>{{ __('Due') }} {{ $assignment->due_at->format('M j') }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quizzes --}}
            @if ($recentQuizzes->isNotEmpty())
                <div class="activity-group">
                    <div class="activity-group-header">
                        <div class="activity-group-icon ic-violet">
                            <i class="fa-solid fa-clipboard-question" aria-hidden="true"></i>
                        </div>
                        <span class="activity-group-label">{{ __('Quizzes') }}</span>
                        <span class="badge blue">{{ $quizzesCount }}</span>
                        <a class="activity-view-all" href="{{ route('student.quizzes.index') }}">{{ __('View all') }} →</a>
                    </div>
                    <div class="activity-card-grid">
                        @foreach ($recentQuizzes as $quiz)
                            @if (($quiz->completed_attempts_count ?? 0) >= (1 + (int)($quiz->retake_attempts ?? 0)))
                                <div class="activity-card activity-card--spent">
                                    <div class="activity-card-top">
                                        <span class="activity-card-title">{{ $quiz->title }}</span>
                                        <span class="item-chip item-chip--done" style="font-size:10px;padding:3px 8px;">{{ __('Done') }}</span>
                                    </div>
                                    <div class="activity-card-meta">
                                        <span>{{ $quiz->subject?->name ?? '-' }}</span>
                                        @if ($quiz->time_limit_minutes)
                                            <span>{{ $quiz->time_limit_minutes }} min</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="activity-card">
                                    <div class="activity-card-top">
                                        <span class="activity-card-title">{{ $quiz->title }}</span>
                                        <form method="post" action="{{ route('student.quizzes.start', $quiz) }}">
                                            @csrf
                                            <button class="activity-start-btn" type="submit">{{ __('Start') }} →</button>
                                        </form>
                                    </div>
                                    <div class="activity-card-meta">
                                        <span>{{ $quiz->subject?->name ?? '-' }}</span>
                                        @if ($quiz->time_limit_minutes)
                                            <span>{{ $quiz->time_limit_minutes }} min</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Exams --}}
            @if ($recentExams->isNotEmpty())
                <div class="activity-group">
                    <div class="activity-group-header">
                        <div class="activity-group-icon ic-gold">
                            <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                        </div>
                        <span class="activity-group-label">{{ __('Exams') }}</span>
                        <span class="badge blue">{{ $examsCount }}</span>
                        <a class="activity-view-all" href="{{ route('student.exams.index') }}">{{ __('View all') }} →</a>
                    </div>
                    <div class="activity-card-grid">
                        @foreach ($recentExams as $exam)
                            @if (($exam->completed_attempts_count ?? 0) >= (1 + (int)($exam->retake_attempts ?? 0)))
                                <div class="activity-card activity-card--spent">
                                    <div class="activity-card-top">
                                        <span class="activity-card-title">{{ $exam->title }}</span>
                                        <span class="item-chip item-chip--done" style="font-size:10px;padding:3px 8px;">{{ __('Done') }}</span>
                                    </div>
                                    <div class="activity-card-meta">
                                        <span>{{ $exam->subject?->name ?? '-' }}</span>
                                        @if ($exam->time_limit_minutes)
                                            <span>{{ $exam->time_limit_minutes }} min</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="activity-card">
                                    <div class="activity-card-top">
                                        <span class="activity-card-title">{{ $exam->title }}</span>
                                        <form method="post" action="{{ route('student.exams.start', $exam) }}">
                                            @csrf
                                            <button class="activity-start-btn" type="submit">{{ __('Start') }} →</button>
                                        </form>
                                    </div>
                                    <div class="activity-card-meta">
                                        <span>{{ $exam->subject?->name ?? '-' }}</span>
                                        @if ($exam->time_limit_minutes)
                                            <span>{{ $exam->time_limit_minutes }} min</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($recentAssignments->isEmpty() && $recentQuizzes->isEmpty() && $recentExams->isEmpty())
                <p class="text-muted" style="padding:16px 0;">{{ __('No activity available for your class yet.') }}</p>
            @endif
        </section>
    </main>
@endsection
