@extends('admin.layout')

@section('title', __('Add Lesson'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ $topic->subject?->name ?? __('Topic') }}</p>
                <h1>{{ $topic->title }}</h1>
                <p class="subtext">{{ __('Add Lesson') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.topics.lessons.index', $topic) }}">{{ __('Back to Lessons') }}</a>
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
                <form class="form-grid" action="{{ route('admin.topics.lessons.store', $topic) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-field">
                        <label for="title">{{ __('Title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" required>
                        @error('title')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label for="content">{{ __('Lesson Text (optional)') }}</label>
                        <textarea id="content" name="content" rows="6">{{ old('content') }}</textarea>
                        @error('content')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="file">{{ __('Resource (PDF or video, optional)') }}</label>
                        <input id="file" name="file" type="file" accept="application/pdf,video/mp4,video/webm,video/ogg">
                        @error('file')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Save Lesson') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
