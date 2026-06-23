@extends('courses.layout')

@section('title', $lesson->title)

@section('hero')
    <div class="courses-hero">
        <div class="hero-inner">
            <div class="hero-breadcrumb">
                <a href="{{ route('courses.index') }}">{{ __('Courses') }}</a>
                <span aria-hidden="true">›</span>
                <a href="{{ route('courses.show', $subject) }}">{{ $subject->name }}</a>
                <span aria-hidden="true">›</span>
                <span>{{ $topic->title }}</span>
            </div>
            <h1>{{ $lesson->title }}</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="lesson-viewer">
        @if ($lesson->content)
            <div class="lesson-content">
                {!! $lesson->content !!}
            </div>
        @endif

        @if ($lesson->file_name)
            <div class="lesson-file-box">
                @if ($lesson->file_type && str_starts_with($lesson->file_type, 'video/'))
                    <video controls playsinline preload="metadata" style="width:100%;max-height:480px;background:#000;border-radius:8px;display:block;margin-bottom:12px;">
                        <source src="{{ route('courses.lesson.file', [$subject, $topic, $lesson]) }}" type="{{ $lesson->file_type }}">
                        {{ __('Your browser does not support video playback.') }}
                    </video>
                @elseif ($lesson->file_type === 'application/pdf')
                    <iframe src="{{ route('courses.lesson.file', [$subject, $topic, $lesson]) }}"
                            style="width:100%;height:640px;border:0;border-radius:8px;display:block;margin-bottom:12px;"
                            title="{{ $lesson->file_name }}"></iframe>
                @endif
            </div>
        @endif

        @if (!$lesson->content && !$lesson->file_name)
            <p style="color:var(--muted);font-size:14px;">{{ __('No content available for this lesson yet.') }}</p>
        @endif

        <div class="lesson-nav">
            @if ($prev)
                <a class="nav-prev" href="{{ route('courses.lesson', [$subject, $topic, $prev]) }}">
                    <span>‹</span>
                    <div>
                        <div class="nav-label">{{ __('Previous') }}</div>
                        <div class="nav-title">{{ $prev->title }}</div>
                    </div>
                </a>
            @else
                <div></div>
            @endif

            @if ($next)
                <a class="nav-next" href="{{ route('courses.lesson', [$subject, $topic, $next]) }}">
                    <div>
                        <div class="nav-label">{{ __('Next') }}</div>
                        <div class="nav-title">{{ $next->title }}</div>
                    </div>
                    <span>›</span>
                </a>
            @endif
        </div>
    </div>
@endsection
