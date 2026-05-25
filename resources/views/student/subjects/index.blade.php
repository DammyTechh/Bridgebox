@extends('student.layout')

@section('title', 'Subjects')

@php
    $iconColors = ['ic-coral', 'ic-blue', 'ic-teal', 'ic-violet', 'ic-gold'];
@endphp

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ __('Subjects') }}</h1>
                <p class="subtext">{{ __('Browse subjects for your class.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('dashboard.student') }}">{{ __('Back to Dashboard') }}</a>
            </div>
        </header>

        @if (!($student?->school_class_id))
            <div class="alert alert-error" role="status">
                <span data-alert-message>{{ __('Your account has no class assigned. Subjects will not appear until a class is assigned.') }}</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Subjects') }}</h4>
                <span class="badge blue">{{ $subjects->total() }}</span>
            </div>

            {{-- Search toolbar --}}
            <div class="table-toolbar" style="margin-bottom:20px;">
                <form class="search-form" method="get" action="{{ route('student.subjects.index') }}">
                    <input class="search-input" type="text" name="q" placeholder="{{ __('Search by name or code') }}" value="{{ $search }}">
                    <button class="btn ghost btn-small" type="submit">{{ __('Search') }}</button>
                    @if ($search)
                        <a class="btn ghost btn-small" href="{{ route('student.subjects.index') }}">{{ __('Clear') }}</a>
                    @endif
                </form>
                <span class="text-muted">Showing {{ $subjects->count() }} of {{ $subjects->total() }}</span>
            </div>

            <div class="subject-card-grid">
                @forelse ($subjects as $i => $subject)
                    @php
                        $colorClass = $iconColors[$i % count($iconColors)];
                        $initials = collect(explode(' ', $subject->name))
                            ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <a class="subject-card" href="{{ route('student.topics.index', ['subject_id' => $subject->id]) }}">
                        @if ($subject->code)
                            <span class="subject-card-code">{{ $subject->code }}</span>
                        @endif
                        <div class="subject-card-icon {{ $colorClass }}">
                            {{ $initials }}
                        </div>
                        <div class="subject-card-body">
                            <h3 class="subject-card-name">{{ $subject->name }}</h3>
                            @if ($subject->description)
                                <p class="subject-card-desc">{{ Str::limit($subject->description, 100) }}</p>
                            @else
                                <p class="subject-card-desc text-muted">{{ $subject->topics_count ?? 0 }} {{ Str::plural('topic', $subject->topics_count ?? 0) }}</p>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="card-empty">
                        <i class="fa-solid fa-book" aria-hidden="true"></i>
                        <p>{{ __('No subjects found.') }}</p>
                    </div>
                @endforelse
            </div>

            @include('admin.users.partials.pagination', ['paginator' => $subjects])
        </section>
    </main>
@endsection
