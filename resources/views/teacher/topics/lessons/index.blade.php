@extends('teacher.layout')

@section('title', 'Topic Lessons')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Topic') }}</p>
                <h1>{{ $topic->title }}</h1>
                <p class="subtext">{{ __('Lessons stored offline for this topic.') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('teacher.topics.lessons.create', $topic) }}">{{ __('Add Lesson') }}</a>
                <a class="btn ghost" href="{{ route('teacher.topics.index') }}">{{ __('Back to Topics') }}</a>
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
                <h4>{{ __('Lessons') }}</h4>
                <span class="badge blue">{{ $lessons->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    <span class="text-muted">Showing {{ $lessons->count() }} of {{ $lessons->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('#') }}</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Text') }}</th>
                                <th>{{ __('File') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lessons as $index => $lesson)
                                <tr data-row-href="{{ route('teacher.topics.lessons.show', [$topic, $lesson]) }}" style="cursor:pointer;">
                                    <td>{{ $lessons->firstItem() + $index }}</td>
                                    <td>{{ $lesson->title }}</td>
                                    <td>{{ $lesson->content ? Str::limit(html_entity_decode(strip_tags($lesson->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 60) : '-' }}</td>
                                    <td>{{ $lesson->file_name ?: '-' }}</td>
                                    <td>{{ $lesson->created_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route('teacher.topics.lessons.edit', [$topic, $lesson]) }}">{{ __('Edit') }}</a>
                                            @if ($lesson->file_path)
                                                <a class="btn ghost btn-small" href="{{ route('teacher.topics.lessons.download', [$topic, $lesson]) }}">{{ __('Download') }}</a>
                                            @endif
                                            <a class="btn ghost btn-small" href="{{ route('teacher.assignments.create', ['lesson_id' => $lesson->id]) }}">{{ __('Assignments') }}</a>
                                            <form method="post" action="{{ route('teacher.topics.lessons.delete', [$topic, $lesson]) }}" data-confirm="{{ __('Delete this lesson?') }}" style="display:inline-block;">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="6">{{ __('No lessons found.') }}</td>
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
