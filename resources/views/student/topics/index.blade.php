@extends('student.layout')

@section('title', 'Topics')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ __('Topics') }}</h1>
                <p class="subtext">{{ __('Browse topics by subject for your class.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('dashboard.student') }}">{{ __('Back to Dashboard') }}</a>
            </div>
        </header>

        @if (!($student?->school_class_id))
            <div class="alert alert-error" role="status">
                <span data-alert-message>{{ __('Your account has no class assigned. Topics will not appear until a class is assigned.') }}</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Topics') }}</h4>
                <span class="badge blue">{{ $topics->total() }}</span>
            </div>

            {{-- Search / filter toolbar --}}
            <div class="table-toolbar" style="margin-bottom:20px;">
                @php($hasFilters = $search || $selectedSubjectId)
                <form class="search-form" method="get" action="{{ route('student.topics.index') }}">
                    <input class="search-input" type="text" name="q" placeholder="{{ __('Search by title') }}" value="{{ $search }}">
                    <select class="search-input" name="subject_id">
                        <option value="" @selected(!$selectedSubjectId)>{{ __('All subjects') }}</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubjectId == $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                    @if ($hasFilters)
                        <a class="btn ghost btn-small" href="{{ route('student.topics.index') }}">{{ __('Clear') }}</a>
                    @endif
                </form>
                <span class="text-muted">Showing {{ $topics->count() }} of {{ $topics->total() }}</span>
            </div>

            <div class="item-card-grid">
            @forelse ($topics as $topic)
                <a class="item-card" href="{{ route('student.lessons.index', ['topic_id' => $topic->id]) }}">
                    <div class="item-card-head">
                        <div class="item-card-icon ic-teal">
                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                        </div>
                        <div class="item-card-tags">
                            @if ($topic->subject)
                                <span class="item-tag">{{ $topic->subject->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item-card-body">
                        <h3 class="item-card-title">{{ $topic->title }}</h3>
                        @if ($topic->description)
                            <p class="item-card-desc">{{ Str::limit($topic->description, 90) }}</p>
                        @endif
                    </div>
                    <div class="item-card-footer">
                        <span class="item-chip">
                            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                            {{ $topic->lessons_count ?? 0 }} {{ Str::plural('Lesson', $topic->lessons_count ?? 0) }}
                        </span>
                        <span class="item-action">{{ __('View Lessons') }} &rarr;</span>
                    </div>
                </a>
            @empty
                <div class="card-empty">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <p>{{ __('No topics found.') }}</p>
                </div>
            @endforelse
            </div>

            @include('admin.users.partials.pagination', ['paginator' => $topics])
        </section>
    </main>
@endsection
