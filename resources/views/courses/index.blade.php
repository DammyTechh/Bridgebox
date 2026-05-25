@extends('courses.layout')

@section('title', __('Courses'))

@section('content')
    <div class="page-header">
        <h1>{{ __('Available Courses') }}</h1>
        <p class="subtext">{{ __('Choose a course below to start learning.') }}</p>
    </div>

    @if ($subjects->isEmpty())
        <div class="empty-state">
            <strong>{{ __('No courses yet') }}</strong>
            <p>{{ __('Content will appear here once courses are added.') }}</p>
        </div>
    @else
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
