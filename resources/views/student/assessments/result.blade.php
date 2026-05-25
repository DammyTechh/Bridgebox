@extends('student.layout')

@section('title', ucfirst($type) . ' Result')

@section('main')
    @php($routePrefix = $type === 'exam' ? 'student.exams' : 'student.quizzes')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ ucfirst($type) }} Result</p>
                <h1>{{ $assessment->title }}</h1>
                <p class="subtext">{{ __('Here is your latest attempt summary.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route($routePrefix . '.index') }}">{{ __('Back to') }} {{ ucfirst($type) }}s</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if (session('message'))
            <div class="alert alert-dismissible {{ session('status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="4000">
                <span data-alert-message>{{ session('message') }}</span>
                <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="{{ __('Dismiss alert') }}">&times;</button>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Attempt Summary') }}</h4>
                <span class="badge gold">{{ __('Completed') }}</span>
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
                            <p>{{ __('Status') }}</p>
                            <span>{{ ($attempt->score ?? 0) >= ($assessment->pass_mark ?? 0) ? __('Passed') : __('Needs Improvement') }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Attempts Left') }}</p>
                            <span>{{ $remainingAttempts }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($remainingAttempts > 0)
            <section class="panel">
                <div class="panel-header">
                    <h4>{{ __('Try Again') }}</h4>
                    <span class="badge blue">{{ __('Optional') }}</span>
                </div>
                <div class="panel-body">
                    <form method="post" action="{{ route($routePrefix . '.start', $assessment) }}">
                        @csrf
                        <button class="btn primary" type="submit">{{ __('Start New Attempt') }}</button>
                    </form>
                </div>
            </section>
        @endif
    </main>
@endsection
