@extends('admin.layout')

@section('title', 'Students')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin User Management') }}</p>
                <h1>{{ __('Students') }}</h1>
                <p class="subtext">{{ __('Search and manage students in the BridgeBox system.') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('admin.users.students.create') }}">{{ __('Add Student') }}</a>
                <a class="btn ghost" href="{{ route('admin.users.students.bulk') }}">{{ __('Bulk Upload') }}</a>
                <a class="btn ghost" href="{{ route('dashboard.admin') }}">{{ __('Back to Dashboard') }}</a>
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

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ __('Students List') }}</h4>
                <span class="badge blue">{{ $students->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedClassId)
                    <form class="search-form" method="get" action="{{ route('admin.users.students.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by name or email') }}" value="{{ $search }}">
                        <select class="search-input" name="class_id">
                            <option value="" @selected(!$selectedClassId)>{{ __('All classes') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route('admin.users.students.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $students->count() }} of {{ $students->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Admission ID') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr class="row-link" data-row-href="{{ route('admin.users.students.show', $student) }}" tabindex="0" role="link" aria-label="View {{ $student->name }}">
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->schoolClass?->name ?? $student->studentProfile?->class ?? '-' }}</td>
                                    <td>{{ $student->studentProfile?->department ?? '-' }}</td>
                                    <td>{{ $student->studentProfile?->admission_id ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $student->is_active ? 'green' : 'rose' }}">
                                            {{ $student->is_active ? __('Active') : __('Disabled') }}
                                        </span>
                                    </td>
                                    <td>{{ $student->created_at?->format('Y-m-d') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="7">{{ __('No students found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $students])
            </div>
        </section>
    </main>
@endsection
