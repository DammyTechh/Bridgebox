@extends('courses.layout')

@section('title', $subject->name)

@section('hero')
    <div class="courses-hero">
        <div class="hero-inner">
            <div class="hero-breadcrumb">
                <a href="{{ route('courses.index') }}">{{ __('Courses') }}</a>
                <span aria-hidden="true">›</span>
                <span>{{ $subject->name }}</span>
            </div>
            @if ($subject->code)
                <div class="hero-eyebrow">{{ $subject->code }}</div>
            @endif
            <h1>{{ $subject->name }}</h1>
            @if ($subject->description)
                <p class="hero-sub">{{ $subject->description }}</p>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @if ($topics->isEmpty())
        <div class="empty-state">
            <strong>{{ __('No topics yet') }}</strong>
            <p>{{ __('Topics will appear here once they are added to this course.') }}</p>
        </div>
    @else
        <div class="section-label">{{ __('Course contents') }}</div>
        <div class="topic-list">
            @foreach ($topics as $topic)
                <div class="topic-block{{ $loop->first ? ' open' : '' }}">
                    <div class="topic-header" role="button" tabindex="0"
                         aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                         aria-controls="topic-lessons-{{ $topic->id }}">
                        <span>{{ $topic->title }}</span>
                        <span class="topic-toggle-icon" aria-hidden="true">›</span>
                    </div>
                    <div class="lesson-list" id="topic-lessons-{{ $topic->id }}"
                         @if(!$loop->first) hidden @endif>
                        @forelse ($topic->lessons as $lesson)
                            <a class="lesson-row"
                               href="{{ route('courses.lesson', [$subject, $topic, $lesson]) }}">
                                <span class="lesson-icon" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 12h6M9 16h6M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9l-7-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span class="lesson-title">{{ $lesson->title }}</span>
                                @if ($lesson->file_name)
                                    <span class="lesson-badge">{{ __('File') }}</span>
                                @endif
                            </a>
                        @empty
                            <div style="padding:14px 20px;font-size:13px;color:var(--muted);">{{ __('No lessons yet.') }}</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.topic-header').forEach(function (header) {
        function toggle() {
            var block = header.closest('.topic-block');
            var open = block.classList.toggle('open');
            var list = block.querySelector('.lesson-list');
            if (open) {
                list.removeAttribute('hidden');
                header.setAttribute('aria-expanded', 'true');
            } else {
                list.setAttribute('hidden', '');
                header.setAttribute('aria-expanded', 'false');
            }
        }
        header.addEventListener('click', toggle);
        header.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        });
    });
</script>
@endpush
