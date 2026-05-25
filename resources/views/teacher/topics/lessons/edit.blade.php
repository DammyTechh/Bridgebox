@extends('teacher.layout')

@section('title', 'Edit Lesson')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ $topic->title }}</p>
                <h1>{{ __('Edit Lesson') }}</h1>
                <p class="subtext">{{ __('Update lesson content or replace the file.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.topics.lessons.show', [$topic, $lesson]) }}">{{ __('View Lesson') }}</a>
                <a class="btn ghost" href="{{ route('teacher.topics.lessons.index', $topic) }}">{{ __('Back to Lessons') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Lesson Details') }}</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route('teacher.topics.lessons.update', [$topic, $lesson]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')

                    <div class="form-field">
                        <label for="title">{{ __('Title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $lesson->title) }}" required>
                        @error('title')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label for="content">{{ __('Lesson Text (optional)') }}</label>
                        <textarea id="content" name="content" rows="6" data-wysiwyg>{{ old('content', $lesson->content) }}</textarea>
                        @error('content')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field" style="margin-top: 40px;">
                        <label for="file">{{ __('Upload New File (PDF or video)') }}</label>
                        @if ($lesson->file_name)
                            <p class="text-muted" style="margin-bottom: 0.5rem;">
                                {{ __('Current file:') }} <strong>{{ $lesson->file_name }}</strong>
                            </p>
                            <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                                <input type="checkbox" name="remove_file" value="1" {{ old('remove_file') ? 'checked' : '' }}>
                                {{ __('Remove current file') }}
                            </label>
                        @endif
                        <input id="file" name="file" type="file" accept="application/pdf,video/mp4,video/webm,video/ogg">
                        @error('file')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
