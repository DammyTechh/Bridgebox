@extends('teacher.layout')

@section('title', $lesson->title)

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ $topic->title }}</p>
                <h1>{{ $lesson->title }}</h1>
                <p class="subtext">{{ __('Lesson details') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('teacher.topics.lessons.edit', [$topic, $lesson]) }}">{{ __('Edit Lesson') }}</a>
                @if ($lesson->file_path)
                    <a class="btn ghost" href="{{ route('teacher.topics.lessons.download', [$topic, $lesson]) }}">{{ __('Download File') }}</a>
                @endif
                <a class="btn ghost" href="{{ route('teacher.topics.lessons.index', $topic) }}">{{ __('Back to Lessons') }}</a>
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
                <h4>{{ __('Lesson Details') }}</h4>
                <span class="text-muted small">{{ $lesson->created_at?->format('Y-m-d') }}</span>
            </div>
            <div class="panel-body">
                @if ($lesson->content)
                    <div class="form-field form-field-full">
                        <label>{{ __('Lesson Text') }}</label>
                        <div class="lesson-content" style="white-space: pre-wrap;">{{ $lesson->content }}</div>
                    </div>
                @endif

                @if ($lesson->file_name)
                    <div class="form-field">
                        <label>{{ __('Attached File') }}</label>
                        <p>{{ $lesson->file_name }}</p>
                    </div>
                @endif

                @if (!$lesson->content && !$lesson->file_name)
                    <p class="text-muted">{{ __('No content available for this lesson.') }}</p>
                @endif
            </div>
        </section>
    </main>
@endsection
