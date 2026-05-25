@extends('student.layout')

@section('title', 'Assignment')

@section('main')
        <main class="main">
            <header class="topbar">
                <div class="greeting">
                    <p class="eyebrow">{{ __('Assignment') }}</p>
                    <h1>{{ $assignment->title }}</h1>
                    <p class="subtext">Lesson: {{ $assignment->lesson?->title ?? '-' }}</p>
                </div>
                <div class="actions">
                    <a class="btn ghost" href="{{ route('student.assignments.index') }}">{{ __('Back to Assignments') }}</a>
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
                    <h4>{{ __('Submit Assignment') }}</h4>
                    <span class="badge gold">{{ __('Required') }}</span>
                </div>
                <div class="panel-body">
                    @if ($assignment->description)
                        <div class="panel" style="margin-bottom:20px;">
                            <div class="panel-header">
                                <h4>{{ __('Assignment Details') }}</h4>
                            </div>
                            <div class="panel-body">
                                <div class="lesson-content" style="white-space:pre-wrap;">{{ $assignment->description }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="item-list" style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px;">
                        <div class="item">
                            <div class="item-info">
                                <p>{{ __('Subject') }}</p>
                                <span>{{ $assignment->lesson?->topic?->subject?->name ?? '-' }}</span>
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
                                <p>{{ __('Deadline') }}</p>
                                <span>{{ $assignment->due_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="item">
                            <div class="item-info">
                                <p>{{ __('Pass Mark') }}</p>
                                <span>{{ $assignment->pass_mark ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    @php
                        $canSubmit = !$submission || !in_array($submission->status, ['submitted', 'graded']);
                    @endphp

                    @if ($canSubmit)
                        <form class="form-grid" action="{{ route('student.assignments.submit', $assignment) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="form-field form-field-full">
                                <label for="content">{{ __('Your Answer (optional)') }}</label>
                                <textarea id="content" name="content" rows="6">{{ old('content', $submission?->content) }}</textarea>
                                @error('content')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="file">{{ __('Upload File (PDF or video)') }}</label>
                                <input id="file" name="file" type="file" accept="application/pdf,video/mp4,video/webm,video/ogg">
                                @error('file')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                                @if ($submission?->file_name)
                                    <div class="text-muted" style="margin-top:6px;">{{ __('Current file:') }} {{ $submission->file_name }}</div>
                                @endif
                            </div>

                            <div class="form-actions">
                                <button class="btn primary" type="submit">{{ __('Submit Assignment') }}</button>
                            </div>
                        </form>
                    @endif

                    @if ($submission)
                        <div class="panel" style="margin-top: 16px;">
                            <div class="panel-header">
                                <h4>{{ __('Your Submission') }}</h4>
                                <span class="badge {{ $submission->status === 'graded' ? 'green' : 'gold' }}">
                                    {{ ucfirst($submission->status ?? 'submitted') }}
                                </span>
                            </div>
                            <div class="panel-body">
                                @if (!$canSubmit)
                                    <p class="text-muted" style="margin-bottom:12px;">
                                        @if ($submission->status === 'graded')
                                            {{ __('Your submission has been graded.') }}
                                        @else
                                            {{ __('Your submission is awaiting review. You cannot resubmit at this time.') }}
                                        @endif
                                    </p>
                                @endif
                                <div class="item">
                                    <div class="item-info">
                                        <p>{{ __('Submitted') }}</p>
                                        <span>{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                    </div>
                                </div>
                                @if ($submission->score !== null)
                                    <div class="item">
                                        <div class="item-info">
                                            <p>{{ __('Score') }}</p>
                                            <span>{{ $submission->score }}{{ $assignment->max_points ? ' / ' . $assignment->max_points : '' }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if ($submission->feedback)
                                    <div class="item">
                                        <div class="item-info">
                                            <p>{{ __('Feedback') }}</p>
                                            <span>{{ $submission->feedback }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if ($submission->file_name)
                                    <div class="item">
                                        <div class="item-info">
                                            <p>{{ __('File') }}</p>
                                            <span>{{ $submission->file_name }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </main>
@endsection
