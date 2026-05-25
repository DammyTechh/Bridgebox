@extends('teacher.layout')

@section('title', __('Grade Submission'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ $submission->user?->name ?? __('Student') }} - {{ $assignment->title }}</h1>
                <p class="subtext">{{ __('Review and grade the submission.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.assignments.submissions.index', $assignment) }}">{{ __('Back to Submissions') }}</a>
                @if ($submission->file_path)
                    <a class="btn ghost" href="{{ route('teacher.assignments.submissions.download', [$assignment, $submission]) }}">{{ __('Download File') }}</a>
                @endif
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if (session('message'))
            <div class="alert alert-dismissible {{ session('status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="4000">
                <span data-alert-message>{{ session('message') }}</span>
                <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="Dismiss alert">&times;</button>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Submission Summary') }}</h4>
                <span class="badge {{ ($submission->status ?? '') === 'graded' ? 'green' : 'blue' }}">{{ $submission->status ?? 'pending' }}</span>
            </div>
            <div class="panel-body">
                <div class="item-list" style="display:flex;flex-direction:column;gap:6px;">
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Student') }}</p>
                            <span>{{ $submission->user?->name ?? __('Unknown') }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Submitted') }}</p>
                            <span>{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Lesson') }}</p>
                            <span>{{ $assignment->lesson?->title ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Topic') }}</p>
                            <span>{{ $assignment->lesson?->topic?->title ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Score') }}</p>
                            <span>
                                @if ($submission->score !== null)
                                    {{ $submission->score }}{{ $assignment->max_points ? ' / ' . $assignment->max_points : '' }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    </div>
                    @if ($assignment->max_points)
                        <div class="item">
                            <div class="item-info">
                                <p>{{ __('Total Mark') }}</p>
                                <span>{{ $assignment->max_points }}</span>
                            </div>
                        </div>
                    @endif
                    @if ($assignment->pass_mark !== null)
                        <div class="item">
                            <div class="item-info">
                                <p>{{ __('Pass Mark') }}</p>
                                <span>{{ $assignment->pass_mark }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Student Response') }}</h4>
                <span class="badge blue">{{ __('Submission') }}</span>
            </div>
            <div class="panel-body">
                <div class="item-list" style="display:flex;flex-direction:column;gap:12px;">
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Text') }}</p>
                            <span>{{ $submission->content ?: '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('File') }}</p>
                            <span>{{ $submission->file_name ?: '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Grade Submission') }}</h4>
                <span class="badge gold">{{ __('Optional') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route('teacher.assignments.submissions.update', [$assignment, $submission]) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-field">
                        <label for="score">{{ __('Score') }}</label>
                        <input id="score" name="score" type="number" min="0" @if ($assignment->max_points) max="{{ $assignment->max_points }}" @endif value="{{ old('score', $submission->score) }}">
                        @if ($assignment->max_points)
                            <small class="text-muted">{{ __('Max') }} {{ $assignment->max_points }}</small>
                        @endif
                        @error('score')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label for="feedback">{{ __('Feedback') }}</label>
                        <textarea id="feedback" name="feedback" rows="4">{{ old('feedback', $submission->feedback) }}</textarea>
                        @error('feedback')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Save Grade') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
