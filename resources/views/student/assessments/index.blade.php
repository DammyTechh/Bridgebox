@extends('student.layout')

@section('title', ucfirst($type) . 's')

@section('main')
    @php($routePrefix = $type === 'exam' ? 'student.exams' : 'student.quizzes')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ ucfirst($type) }}s</h1>
                <p class="subtext">Take {{ $type }}s assigned to your class.</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('dashboard.student') }}">{{ __('Back to Dashboard') }}</a>
            </div>
        </header>

        @if (session('message'))
            <div class="alert alert-dismissible {{ session('status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="4000">
                <span data-alert-message>{{ session('message') }}</span>
                <button class="alert-close" type="button" data-alert-close aria-label="{{ __('Dismiss') }}">&times;</button>
            </div>
        @endif

        @if (!($student?->school_class_id))
            <div class="alert alert-error" role="status">
                <span data-alert-message>Your account has no class assigned. {{ ucfirst($type) }}s will not appear until a class is assigned.</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ ucfirst($type) }} List</h4>
                <span class="badge blue">{{ $assessments->total() }}</span>
            </div>

            <div class="table-toolbar" style="margin-bottom:20px;">
                <form class="search-form" method="get" action="{{ route($routePrefix . '.index') }}">
                    <input class="search-input" type="text" name="q" placeholder="{{ __('Search by title') }}" value="{{ $search }}">
                    <button class="btn ghost btn-small" type="submit">{{ __('Search') }}</button>
                    @if ($search)
                        <a class="btn ghost btn-small" href="{{ route($routePrefix . '.index') }}">{{ __('Clear') }}</a>
                    @endif
                </form>
                <span class="text-muted">Showing {{ $assessments->count() }} of {{ $assessments->total() }}</span>
            </div>

            <div class="item-card-grid">
                @forelse ($assessments as $assessment)
                    @php($allowedAttempts = 1 + (int) ($assessment->retake_attempts ?? 0))
                    @php($usedAttempts   = $assessment->completed_attempts_count ?? 0)
                    @php($canAttempt    = $usedAttempts < $allowedAttempts)
                    <div class="item-card {{ !$canAttempt ? 'item-card--spent' : '' }}">
                        <div class="item-card-head">
                            <div class="item-card-icon ic-coral">
                                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                            </div>
                            <div class="item-card-tags">
                                @if ($assessment->subject)
                                    <span class="item-tag">{{ $assessment->subject->name }}</span>
                                @endif
                                @if ($assessment->topic)
                                    <span class="item-tag">{{ $assessment->topic->title }}</span>
                                @endif
                                @if ($assessment->time_limit_minutes)
                                    <span class="item-tag item-tag--blue">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        Duration: {{ $assessment->time_limit_minutes }} {{ Str::plural('minute', $assessment->time_limit_minutes) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="item-card-body">
                            <h3 class="item-card-title">{{ $assessment->title }}</h3>
                            @if ($assessment->description)
                                <p class="item-card-desc">{{ Str::limit($assessment->description, 110) }}</p>
                            @endif
                        </div>
                        <div class="item-card-footer">
                            <span class="badge {{ $canAttempt ? 'blue' : 'muted' }}">
                                {{ $usedAttempts }} / {{ $allowedAttempts }} {{ Str::plural('Attempt', $allowedAttempts) }}
                            </span>
                            @if ($canAttempt)
                                <form method="post" action="{{ route($routePrefix . '.start', $assessment) }}">
                                    @csrf
                                    <button class="item-action-btn" type="submit">{{ __('Take Assessment') }} &rarr;</button>
                                </form>
                            @else
                                <span class="item-action item-action--muted">{{ __('No attempts left') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card-empty">
                        <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                        <p>No {{ $type }}s found.</p>
                    </div>
                @endforelse
            </div>

            @include('admin.users.partials.pagination', ['paginator' => $assessments])
        </section>
    </main>
@endsection