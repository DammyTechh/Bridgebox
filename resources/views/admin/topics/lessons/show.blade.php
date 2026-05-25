@extends('admin.layout')

@section('title', __('Lesson'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Topic') }}</p>
                <h1>{{ $lesson->title }}</h1>
                <p class="subtext" style="color: white;">{{ $topic->title }} · {{ $lesson->created_at?->format('Y-m-d') ?? '-' }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.topics.lessons.edit', [$topic, $lesson]) }}">{{ __('Edit') }}</a>
                <a class="btn ghost" href="{{ route('admin.topics.lessons.index', $topic) }}">{{ __('Back to Lessons') }}</a>
                <a class="btn ghost" href="{{ route('admin.topics.lessons.download', [$topic, $lesson]) }}">{{ __('Download') }}</a>
                <form action="{{ route('admin.topics.lessons.delete', [$topic, $lesson]) }}" method="post" style="display:inline-block;">
                    @csrf
                    @method('delete')
                    <button class="btn ghost" type="submit" data-confirm="{{ __('Delete this lesson?') }}">{{ __('Delete') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Lesson Content') }}</h4>
                <span class="badge gold">{{ __('Details') }}</span>
            </div>
            <div class="panel-body">
                @if ($lesson->content)
                    <div class="lesson-content">
                        {!! $lesson->content !!}
                    </div>
                @else
                    <p class="text-muted">{{ __('No text content provided for this lesson.') }}</p>
                @endif

                @if ($lesson->file_path)
                    <div class="panel" style="margin-top:16px;">
                        <div class="panel-header">
                            <h4>{{ __('Lesson Resource') }}</h4>
                            <span class="badge blue">{{ $lesson->file_type ?: 'file' }}</span>
                        </div>
                        <div class="panel-body">
                            @if ($lesson->file_type && str_starts_with($lesson->file_type, 'video'))
                                <video controls playsinline preload="metadata" style="width:100%;max-height:480px;background:#000;border-radius:6px;">
                                    <source src="{{ route('admin.topics.lessons.file', [$topic, $lesson]) }}" type="{{ $lesson->file_type }}">
                                    {{ __('Your browser does not support the video tag.') }}
                                </video>
                            @endif
                            @if ($lesson->file_type && $lesson->file_type === 'application/pdf')
                                <div style="margin-top:8px;">
                                    <iframe src="{{ route('admin.topics.lessons.file', [$topic, $lesson]) }}" style="width:100%;height:600px;border:0;border-radius:6px;"></iframe>
                                </div>
                            @endif
                            <div style="margin-top:8px;">
                                <a class="btn ghost btn-small" href="{{ route('admin.topics.lessons.file', [$topic, $lesson]) }}">{{ __('Open File') }}</a>
                                <a class="btn ghost btn-small" href="{{ route('admin.topics.lessons.download', [$topic, $lesson]) }}" download>{{ __('Download') }}</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection
