@extends('teacher.layout')

@section('title', 'Topics')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Topics') }}</h1>
                <p class="subtext">{{ __('Topics tied to your subjects and classes.') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('teacher.topics.create') }}">{{ __('Add Topic') }}</a>
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
                <h4>{{ __('Topics List') }}</h4>
                <span class="badge blue">{{ $topics->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedClassId || $selectedSubjectId)
                    <form class="search-form" method="get" action="{{ route('teacher.topics.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by title') }}" value="{{ $search }}">>
                        <select class="search-input" name="class_id">
                            <option value="" @selected(!$selectedClassId)>{{ __('All classes') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <select class="search-input" name="subject_id">
                            <option value="" @selected(!$selectedSubjectId)>{{ __('All subjects') }}</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected($selectedSubjectId == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route('teacher.topics.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $topics->count() }} of {{ $topics->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topics as $topic)
                                <tr>
                                    <td>{{ $topic->title }}</td>
                                    <td>{{ $topic->schoolClass?->name ?? '-' }}</td>
                                    <td>{{ $topic->subject?->name ?? '-' }}</td>
                                    <td>{{ Str::limit($topic->description, 60) }}</td>
                                    <td>{{ $topic->created_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route('teacher.topics.lessons.index', $topic) }}">{{ __('Lessons') }}</a>
                                            <a class="btn ghost btn-small" href="{{ route('teacher.topics.edit', $topic) }}">{{ __('Edit') }}</a>
                                            <form method="post" action="{{ route('teacher.topics.delete', $topic) }}" data-confirm="{{ __('Delete this topic?') }}" style="display:inline-block;">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="6">{{ __('No topics found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $topics])
            </div>
        </section>
    </main>
@endsection
