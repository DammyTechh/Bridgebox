@extends('admin.layout')

@section('title', 'Student Progress')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin Student Progress') }}</p>
                <h1>{{ $student->name }}</h1>
                <p class="subtext">{{ __('Class') }}: {{ $student->schoolClass?->name ?? '-' }} | {{ __('Department') }}: {{ $student->studentProfile?->department ?? '-' }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.users.students.index') }}">{{ __('Back to Students') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @php
            $attemptReviewRouteQuiz = 'admin.quizzes.attempts.show';
            $attemptReviewRouteExam = 'admin.exams.attempts.show';
            $submissionReviewRoute = 'admin.assignments.submissions.show';
        @endphp

        @include('partials.student-progress')
    </main>
@endsection
