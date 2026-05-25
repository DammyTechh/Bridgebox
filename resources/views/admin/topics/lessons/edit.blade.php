@extends('admin.layout')

@section('title', __('Edit Lesson'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Topic') }}: {{ $topic->title }}</p>
                <h1>{{ __('Edit Lesson') }}</h1>
                <p class="subtext">{{ $lesson->title }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.topics.lessons.index', $topic) }}">{{ __('Back to Lessons') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Lesson Details') }}</h4>
                <span class="badge gold">{{ __('Edit') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route('admin.topics.lessons.update', [$topic, $lesson]) }}" method="post" enctype="multipart/form-data">
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

                    <div class="form-field">
                        <label for="file">{{ __('Resource (PDF or video)') }}</label>
                        @if ($lesson->file_name)
                            <p class="text-muted" style="margin-bottom:6px;font-size:13px;">
                                {{ __('Current resource') }}: <strong>{{ $lesson->file_name }}</strong>
                            </p>
                            <label class="checkbox" style="margin-bottom:8px;">
                                <input type="checkbox" name="remove_file" value="1" {{ old('remove_file') ? 'checked' : '' }}>
                                <span>{{ __('Remove current file') }}</span>
                            </label>
                        @endif
                        <input id="file" name="file" type="file" accept="application/pdf,video/mp4,video/webm,video/ogg">
                        @error('file')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Save Changes') }}</button>
                        <a class="btn ghost" href="{{ route('admin.topics.lessons.index', $topic) }}">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
