@extends('courses.layout')

@section('title', __('Courses'))

@section('hero')
    @php
        $courseCount = $subjects->count();
        $topicTotal = $subjects->sum('topics_count');
        $lessonTotal = $subjects->sum('lessons_count');
    @endphp
    <div class="courses-hero">
        <div class="hero-inner">
            <span class="hero-pill">
                <span class="dot" aria-hidden="true"></span>
                {{ __('Works offline · no internet needed') }}
            </span>
            <div class="hero-eyebrow">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 6.5A2.5 2.5 0 015.5 4H10v12H5.5A2.5 2.5 0 013 13.5v-7z" fill="currentColor"/>
                    <path d="M14 4h4.5A2.5 2.5 0 0121 6.5v7A2.5 2.5 0 0118.5 16H14V4z" fill="currentColor" opacity="0.7"/>
                </svg>
                {{ __('Learning Library') }}
            </div>
            <h1>{{ __('Available Courses') }}</h1>
            <p class="hero-sub">{{ __('Choose a course below to start learning. Everything here runs straight from the BridgeBox.') }}</p>

            @if ($courseCount)
                <div class="hero-stats">
                    <div class="hero-stat">
                        <b>{{ $courseCount }}</b>
                        <span>{{ Str::plural(__('Course'), $courseCount) }}</span>
                    </div>
                    <div class="hero-stat">
                        <b>{{ $topicTotal }}</b>
                        <span>{{ Str::plural(__('Topic'), $topicTotal) }}</span>
                    </div>
                    <div class="hero-stat">
                        <b>{{ $lessonTotal }}</b>
                        <span>{{ Str::plural(__('Lesson'), $lessonTotal) }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @if ($subjects->isEmpty())
        <div class="empty-state">
            <strong>{{ __('No courses yet') }}</strong>
            <p>{{ __('Content will appear here once courses are added.') }}</p>
        </div>
    @else
        <div class="section-label">{{ __('Browse all courses') }}</div>
        <div class="course-grid">
            @foreach ($subjects as $subject)
                <a class="course-card{{ $subject->feature_image ? ' course-card-has-image' : '' }}" href="{{ route('courses.show', $subject) }}">
                    @if ($subject->feature_image)
                        <div class="course-card-image">
                            <img src="{{ route('courses.subject.image', $subject) }}" alt="{{ $subject->name }}">
                        </div>
                    @endif
                    @if ($subject->code)
                        <span class="course-code">{{ $subject->code }}</span>
                    @endif
                    <h2>{{ $subject->name }}</h2>
                    @if ($subject->description)
                        <p class="course-desc">{{ $subject->description }}</p>
                    @endif
                    <div class="course-meta">
                        <span>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ $subject->topics_count }} {{ Str::plural('topic', $subject->topics_count) }}
                        </span>
                        <span>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $subject->lessons_count }} {{ Str::plural('lesson', $subject->lessons_count) }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
