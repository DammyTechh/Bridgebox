@extends('teacher.layout')

@section('title', 'Departments')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Departments') }}</h1>
                <p class="subtext">{{ __('Create and manage departments in the system.') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('teacher.departments.create') }}">{{ __('Add Department') }}</a>
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
                <h4>{{ __('Departments List') }}</h4>
                <span class="badge blue">{{ $departments->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    <form class="search-form" method="get" action="{{ route('teacher.departments.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by name or code') }}" value="{{ $search }}">>
                        <button class="btn ghost btn-small" type="submit">{{ __('Search') }}</button>
                        @if ($search)
                            <a class="btn ghost btn-small" href="{{ route('teacher.departments.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $departments->count() }} of {{ $departments->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Slug') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($departments as $department)
                                <tr>
                                    <td>{{ $department->name }}</td>
                                    <td>{{ $department->code ?: '-' }}</td>
                                    <td>{{ Str::limit($department->description, 60) }}</td>
                                    <td>{{ $department->created_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route('teacher.departments.edit', $department) }}">{{ __('Edit') }}</a>
                                            <form method="post" action="{{ route('teacher.departments.delete', $department) }}" data-confirm="{{ __('Delete this department?') }}" style="display:inline-block;">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="5">{{ __('No departments found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $departments])
            </div>
        </section>
    </main>
@endsection
