@extends('admin.layout')

@section('title', $installMode->isGeneric() ? __('Courses') : __('Subjects'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin') }}</p>
                <h1>{{ $installMode->isGeneric() ? __('Courses') : __('Subjects') }}</h1>
                <p class="subtext">{{ $installMode->isGeneric() ? __('Manage courses in the learning library.') : __('Create and manage subjects in the system.') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('admin.subjects.create') }}">{{ $installMode->isGeneric() ? __('Add Course') : __('Add Subject') }}</a>
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
                <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="{{ __('Dismiss alert') }}">&times;</button>
            </div>
        @endif

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ $installMode->isGeneric() ? __('Courses List') : __('Subjects List') }}</h4>
                <span class="badge blue">{{ $subjects->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedSectionId)
                    <form class="search-form" method="get" action="{{ route('admin.subjects.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by name or code') }}" value="{{ $search }}">
                        @if ($installMode->isSchool())
                        <select class="search-input" name="section_id">
                            <option value="" @selected(!$selectedSectionId)>{{ __('All sections') }}</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected($selectedSectionId == $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </select>
                        @endif
                        <button class="btn ghost btn-small" type="submit">{{ __('Search') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route('admin.subjects.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $subjects->count() }} of {{ $subjects->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Slug') }}</th>
                                @if ($installMode->isSchool())
                                <th>{{ __('Section') }}</th>
                                @endif
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjects as $subject)
                                <tr>
                                    <td>{{ $subject->name }}</td>
                                    <td>{{ $subject->code ?: '-' }}</td>
                                    @if ($installMode->isSchool())
                                    <td>{{ $subject->section?->name ?? '-' }}</td>
                                    @endif
                                    <td>{{ Str::limit($subject->description, 60) }}</td>
                                    <td>{{ $subject->created_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route('admin.subjects.edit', $subject) }}">{{ __('Edit') }}</a>
                                            <form method="post" action="{{ route('admin.subjects.delete', $subject) }}" data-confirm="{{ __('Delete this subject?') }}" style="display:inline-block;">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="6">{{ __('No subjects found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $subjects])
            </div>
        </section>
    </main>
@endsection
