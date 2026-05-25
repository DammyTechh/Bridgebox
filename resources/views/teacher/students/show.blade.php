@extends('teacher.layout')

@section('title', 'Student Details')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher Student') }}</p>
                <h1>{{ $student->name }}</h1>
                <p class="subtext">{{ __('View student details and actions.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.students.index') }}">{{ __('Back to Students') }}</a>
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
                <h4>{{ __('Student Details') }}</h4>
                <span class="badge {{ $student->is_active ? 'green' : 'rose' }}">{{ $student->is_active ? 'Active' : 'Disabled' }}</span>
            </div>
            <div class="panel-body">
                <div class="item-list" style="display:flex;flex-direction:column;gap:6px;">
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Name') }}</p>
                            <span>{{ $student->name }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Email') }}</p>
                            <span>{{ $student->email }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Phone') }}</p>
                            <span>{{ $student->phone ?: '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Class') }}</p>
                            <span>{{ $student->schoolClass?->name ?? $student->studentProfile?->class ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Department') }}</p>
                            <span>{{ $student->studentProfile?->department ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Admission ID') }}</p>
                            <span>{{ $student->studentProfile?->admission_id ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Created') }}</p>
                            <span>{{ $student->created_at?->format('Y-m-d') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        

        @php
            $attemptReviewRouteQuiz = 'teacher.quizzes.attempts.show';
            $attemptReviewRouteExam = 'teacher.exams.attempts.show';
            $submissionReviewRoute = 'teacher.assignments.submissions.show';
        @endphp

        @include('partials.student-progress')
        
        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Actions') }}</h4>
                <span class="badge gold">{{ __('Manage') }}</span>
            </div>
            <div class="panel-body">
                <div class="table-actions">
                    <a class="btn ghost btn-small" href="{{ route('teacher.students.edit', $student) }}">{{ __('Edit') }}</a>
                    <form method="post" action="{{ route('teacher.students.delete', $student) }}" data-confirm="{{ __('Delete this student account?') }}">
                        @csrf
                        @method('delete')
                        <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
@endsection
