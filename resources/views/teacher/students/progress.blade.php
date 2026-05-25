@extends('teacher.layout')

@section('title', 'Student Progress')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher Student Progress') }}</p>
                <h1>{{ $student->name }}</h1>
                <p class="subtext">Class: {{ $student->schoolClass?->name ?? '-' }} | Department: {{ $student->studentProfile?->department ?? '-' }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.students.index') }}">{{ __('Back to Students') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @php
            $attemptReviewRouteQuiz = 'teacher.quizzes.attempts.show';
            $attemptReviewRouteExam = 'teacher.exams.attempts.show';
            $submissionReviewRoute = 'teacher.assignments.submissions.show';
        @endphp

        @include('partials.student-progress')
    </main>
@endsection
