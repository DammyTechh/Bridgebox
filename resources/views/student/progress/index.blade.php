@extends('student.layout')

@section('title', 'Progress')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ __('Progress') }}</h1>
                <p class="subtext">{{ __('Track your recent activity and progress.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('dashboard.student') }}">{{ __('Back to Dashboard') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="quick-tabs">
            <div class="tab" style="--accent: #4a7bd1; --d: 0.05s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Lessons') }}</p>
                    <span>{{ $lessonsCount }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #e56b6f; --d: 0.1s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Topics') }}</p>
                    <span>{{ $topicsCount }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #f2b84b; --d: 0.15s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Assignments') }}</p>
                    <span>{{ $submissionsCount }} / {{ $assignmentsCount }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #56c1a7; --d: 0.2s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Assessments') }}</p>
                    <span>{{ $attemptsCompleted }} / {{ $quizzesCount + $examsCount }}</span>
                </div>
            </div>
        </section>

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ __('Recent Assessments') }}</h4>
                <span class="badge blue">{{ $recentAttempts->count() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Assessment') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('Topic') }}</th>
                                <th>{{ __('Score') }}</th>
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAttempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->assessment?->title ?? '-' }}</td>
                                    <td>{{ ucfirst($attempt->assessment?->type ?? '-') }}</td>
                                    <td>{{ $attempt->assessment?->subject?->name ?? '-' }}</td>
                                    <td>{{ $attempt->assessment?->topic?->title ?? '-' }}</td>
                                    <td>{{ $attempt->score ?? 0 }} / {{ $attempt->total ?? 0 }}</td>
                                    <td>{{ $attempt->completed_at?->format('Y-m-d') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="6">{{ __('No assessment attempts yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ __('Recent Assignment Submissions') }}</h4>
                <span class="badge blue">{{ $recentSubmissions->count() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Assignment') }}</th>
                                <th>{{ __('Lesson') }}</th>
                                <th>{{ __('Topic') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSubmissions as $submission)
                                <tr>
                                    <td>{{ $submission->assignment?->title ?? '-' }}</td>
                                    <td>{{ $submission->assignment?->lesson?->title ?? '-' }}</td>
                                    <td>{{ $submission->assignment?->lesson?->topic?->title ?? '-' }}</td>
                                    <td>{{ $submission->status ?? __('submitted') }}</td>
                                    <td>{{ $submission->submitted_at?->format('Y-m-d') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="5">{{ __('No submissions yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
@endsection
