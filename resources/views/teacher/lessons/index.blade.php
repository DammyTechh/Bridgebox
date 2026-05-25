@extends('teacher.layout')

@section('title', __('Lessons'))

@php($navSection = 'lessons')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Lessons') }}</h1>
                <p class="subtext">{{ __('View and manage lessons across all your topics.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.topics.index') }}">{{ __('Go to Topics') }}</a>
                <a class="btn ghost" href="{{ route('dashboard.teacher') }}">{{ __('Back to Dashboard') }}</a>
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

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ __('Lessons List') }}</h4>
                <span class="badge blue">{{ $lessons->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedTopicId || $selectedSubjectId)
                    <form class="search-form" method="get" action="{{ route('teacher.lessons.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by title') }}" value="{{ $search }}">
                        <select class="search-input" name="subject_id">
                            <option value="" @selected(!$selectedSubjectId)>{{ __('All subjects') }}</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected($selectedSubjectId == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <select class="search-input" name="topic_id">
                            <option value="" @selected(!$selectedTopicId)>{{ __('All topics') }}</option>
                            @foreach ($topics as $topic)
                                <option value="{{ $topic->id }}" @selected($selectedTopicId == $topic->id)>{{ $topic->title }}</option>
                            @endforeach
                        </select>
                        <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route('teacher.lessons.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $lessons->count() }} of {{ $lessons->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('#') }}</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Topic') }}</th>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('Text') }}</th>
                                <th>{{ __('File') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lessons as $index => $lesson)
                                <tr @if($lesson->topic) data-row-href="{{ route('teacher.topics.lessons.show', [$lesson->topic, $lesson]) }}" style="cursor:pointer;" @endif>
                                    <td>{{ $lessons->firstItem() + $index }}</td>
                                    <td>{{ $lesson->title }}</td>
                                    <td>{{ $lesson->topic?->title ?? '-' }}</td>
                                    <td>{{ $lesson->topic?->subject?->name ?? '-' }}</td>
                                    <td>{{ $lesson->content ? Str::limit(html_entity_decode(strip_tags($lesson->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 50) : '-' }}</td>
                                    <td>{{ $lesson->file_name ?: '-' }}</td>
                                    <td>{{ $lesson->created_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            @if ($lesson->topic)
                                                <a class="btn ghost btn-small" href="{{ route('teacher.topics.lessons.edit', [$lesson->topic, $lesson]) }}">{{ __('Edit') }}</a>
                                                @if ($lesson->file_path)
                                                    <a class="btn ghost btn-small" href="{{ route('teacher.topics.lessons.download', [$lesson->topic, $lesson]) }}">{{ __('Download') }}</a>
                                                @endif
                                                <a class="btn ghost btn-small" href="{{ route('teacher.topics.lessons.create', $lesson->topic) }}">{{ __('Add Lesson') }}</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="8">{{ __('No lessons found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $lessons])
            </div>
        </section>
    </main>
@endsection
