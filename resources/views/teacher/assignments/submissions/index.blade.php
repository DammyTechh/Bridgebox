@extends('teacher.layout')

@section('title', 'Assignment Submissions')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ $assignment->title }}</h1>
                <p class="subtext">Lesson: {{ $assignment->lesson?->title ?? '-' }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.assignments.index') }}">{{ __('Back to Assignments') }}</a>
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
                <h4>{{ __('Submissions') }}</h4>
                <span class="badge blue">{{ $submissions->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    <span class="text-muted">Showing {{ $submissions->count() }} of {{ $submissions->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('#') }}</th>
                                <th>{{ __('Student') }}</th>
                                <th>{{ __('Submitted') }}</th>
                                <th>{{ __('Score') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Text') }}</th>
                                <th>{{ __('File') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $index => $submission)
                                <tr>
                                    <td>{{ $submissions->firstItem() + $index }}</td>
                                    <td>{{ $submission->user?->name ?? 'Unknown' }}</td>
                                    <td>{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>
                                        @if ($submission->score !== null)
                                            {{ $submission->score }}{{ $assignment->max_points ? ' / ' . $assignment->max_points : '' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $submission->status ?? 'pending' }}</td>
                                    <td>{{ $submission->content ? Str::limit($submission->content, 50) : '-' }}</td>
                                    <td>{{ $submission->file_name ?: '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route('teacher.assignments.submissions.show', [$assignment, $submission]) }}">{{ __('Grade') }}</a>
                                            @if ($submission->file_path)
                                                <a class="btn ghost btn-small" href="{{ route('teacher.assignments.submissions.download', [$assignment, $submission]) }}">{{ __('Download') }}</a>
                                            @endif
                                            <form method="post" action="{{ route('teacher.assignments.submissions.delete', [$assignment, $submission]) }}" data-confirm="{{ __('Delete this submission?') }}" style="display:inline-block;">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="8">{{ __('No submissions found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $submissions])
            </div>
        </section>
    </main>
@endsection
