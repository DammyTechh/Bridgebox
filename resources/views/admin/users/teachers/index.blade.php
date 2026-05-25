@extends('admin.layout')

@section('title', __('Teachers'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin User Management') }}</p>
                <h1>{{ __('Teachers') }}</h1>
                <p class="subtext">{{ __('Search and manage teachers in the BridgeBox system.') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('admin.users.teachers.create') }}">{{ __('Add Teacher') }}</a>
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
                <h4>{{ __('Teachers List') }}</h4>
                <span class="badge blue">{{ $teachers->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedClassId)
                    <form class="search-form" method="get" action="{{ route('admin.users.teachers.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by name or email') }}" value="{{ $search }}">
                        <select class="search-input" name="class_id">
                            <option value="" @selected(!$selectedClassId)>{{ __('All classes') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route('admin.users.teachers.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $teachers->count() }} of {{ $teachers->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->name }}</td>
                                    <td>{{ $teacher->email }}</td>
                                        <td>{{ $teacher->phone ?: '-' }}</td>
                                        <td>{{ $teacher->schoolClass?->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $teacher->is_active ? 'green' : 'rose' }}">
                                                {{ $teacher->is_active ? __('Active') : __('Disabled') }}
                                            </span>
                                        </td>
                                        <td>{{ $teacher->created_at?->format('Y-m-d') ?? '-' }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <form method="post" action="{{ route('admin.users.impersonate', $teacher) }}" data-confirm="{{ __('Login as this teacher?') }}">
                                                    @csrf
                                                    <button class="btn ghost btn-small" type="submit" @disabled(!$teacher->is_active)>{{ __('Login As') }}</button>
                                                </form>
                                                <a class="btn ghost btn-small" href="{{ route('admin.users.teachers.edit', $teacher) }}">{{ __('Edit') }}</a>
                                                <form method="post" action="{{ route('admin.users.toggle', $teacher) }}" data-confirm="{{ __('Are you sure?') }}">
                                                    @csrf
                                                    <button class="btn ghost btn-small" type="submit">
                                                        {{ $teacher->is_active ? __('Disable') : __('Enable') }}
                                                    </button>
                                            </form>
                                            <form method="post" action="{{ route('admin.users.reset', $teacher) }}" data-confirm="{{ __('Reset password for this teacher?') }}">
                                                @csrf
                                                <button class="btn ghost btn-small" type="submit">{{ __('Reset Password') }}</button>
                                            </form>
                                            <form method="post" action="{{ route('admin.users.delete', $teacher) }}" data-confirm="{{ __('Delete this teacher account?') }}">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="6">{{ __('No teachers found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $teachers])
            </div>
        </section>
    </main>
@endsection
