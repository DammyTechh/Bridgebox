@extends('admin.layout')

@php($navSection = $section ?? null)

@section('title', "Admin {$title}")

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin User Management') }}</p>
                <h1>{{ $title }}</h1>
                <p class="subtext">{{ __('This section is ready for user management in the next milestones.') }}</p>
            </div>
            <div class="actions">
                @if ($section === 'teachers')
                    <a class="btn primary" href="{{ route('admin.users.teachers.create') }}">{{ __('Add Teacher') }}</a>
                @elseif ($section === 'students')
                    <a class="btn primary" href="{{ route('admin.users.students.create') }}">{{ __('Add Student') }}</a>
                @endif
                <a class="btn ghost" href="{{ route('dashboard.admin') }}">{{ __('Back to Dashboard') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ $title }} Management</h4>
                <span class="badge gold">{{ __('Coming Soon') }}</span>
            </div>
            <div class="panel-body">
                <div class="item">
                    <div class="item-info">
                        <p>{{ __('Placeholder') }}</p>
                        <span>{{ __('Creation, search, and actions will appear here.') }}</span>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
