@extends('teacher.layout')

@section('title', 'Subjects')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Subjects') }}</h1>
                <p class="subtext">{{ __('Subjects available for your classes.') }}</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('teacher.subjects.create') }}">{{ __('Add Subject') }}</a>
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
                <h4>{{ __('Subjects List') }}</h4>
                <span class="badge blue">{{ $subjects->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedSectionId)
                    <form class="search-form" method="get" action="{{ route('teacher.subjects.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by name or code') }}" value="{{ $search }}">>
                        <select class="search-input" name="section_id" @disabled($sections->count() === 0)>
                            <option value="" @selected(!$selectedSectionId)>{{ __('All sections') }}</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected($selectedSectionId == $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn ghost btn-small" type="submit">{{ __('Search') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route('teacher.subjects.index') }}">{{ __('Clear') }}</a>
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
                                <th>{{ __('Section') }}</th>
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
                                    <td>{{ $subject->section?->name ?? '-' }}</td>
                                    <td>{{ Str::limit($subject->description, 60) }}</td>
                                    <td>{{ $subject->created_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route('teacher.subjects.edit', $subject) }}">{{ __('Edit') }}</a>
                                            <form method="post" action="{{ route('teacher.subjects.delete', $subject) }}" data-confirm="{{ __('Delete this subject?') }}" style="display:inline-block;">
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
