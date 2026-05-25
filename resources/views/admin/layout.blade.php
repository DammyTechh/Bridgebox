<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BridgeBox Admin')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/quill/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/editor.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-alerts.css') }}">
</head>
<body data-toolbar-label="{{ __('Filter') }}">
    @php($navSection = $navSection ?? null)
    <div class="page">
        <button class="hamburger-btn" id="sidebar-toggle" aria-label="{{ __('Open navigation menu') }}" aria-expanded="false" aria-controls="sidebar">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-mark">
                    <img class="brand-logo" src="{{ asset('assets/images/favicon.png') }}" alt="BridgeBox logo">
                </div>
                <span class="brand-name">BridgeBox</span>
            </div>
            <nav class="nav">
                <a class="nav-item {{ request()->routeIs('dashboard.admin*') ? 'active' : '' }}" href="{{ route('dashboard.admin') }}" aria-label="Admin control room">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    <span>{{ __('Home') }}</span>
                </a>
                @if ($installMode->isSchool())
                <a class="nav-item {{ (request()->routeIs('admin.users.teachers.*') || $navSection === 'teachers') ? 'active' : '' }}" href="{{ route('admin.users.teachers.index') }}" aria-label="Manage teachers">
                    <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i>
                    <span>{{ __('Teachers') }}</span>
                </a>
                <a class="nav-item {{ (request()->routeIs('admin.users.students.*') || $navSection === 'students') ? 'active' : '' }}" href="{{ route('admin.users.students.index') }}" aria-label="Manage students">
                    <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                    <span>{{ __('Students') }}</span>
                </a>
                <a class="nav-item {{ (request()->routeIs('admin.classes.*') || $navSection === 'classes') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}" aria-label="Manage classes">
                    <i class="fa-solid fa-people-roof" aria-hidden="true"></i>
                    <span>{{ __('Classes') }}</span>
                </a>
                @endif
                <a class="nav-item {{ (request()->routeIs('admin.subjects.*') || $navSection === 'subjects') ? 'active' : '' }}" href="{{ route('admin.subjects.index') }}" aria-label="Manage subjects">
                    <i class="fa-solid fa-book" aria-hidden="true"></i>
                    <span>{{ $installMode->isGeneric() ? __('Courses') : __('Subjects') }}</span>
                </a>
                @if ($installMode->isSchool())
                <a class="nav-item {{ (request()->routeIs('admin.departments.*') || $navSection === 'departments') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}" aria-label="Manage departments">
                    <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                    <span>{{ __('Departments') }}</span>
                </a>
                @endif
                <a class="nav-item {{ (request()->routeIs('admin.topics.*') || $navSection === 'topics') ? 'active' : '' }}" href="{{ route('admin.topics.index') }}" aria-label="Manage topics">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>{{ __('Topics') }}</span>
                </a>
                <a class="nav-item {{ (request()->routeIs('admin.assignments.*') || $navSection === 'assignments') ? 'active' : '' }}" href="{{ route('admin.assignments.index') }}" aria-label="Manage assignments">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    <span>{{ __('Assignments') }}</span>
                </a>
                <a class="nav-item {{ (request()->routeIs('admin.quizzes.*') || $navSection === 'quizzes') ? 'active' : '' }}" href="{{ route('admin.quizzes.index') }}" aria-label="Manage quizzes">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                    <span>{{ __('Quizzes') }}</span>
                </a>
                <a class="nav-item {{ (request()->routeIs('admin.exams.*') || $navSection === 'exams') ? 'active' : '' }}" href="{{ route('admin.exams.index') }}" aria-label="Manage exams">
                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                    <span>{{ __('Exams') }}</span>
                </a>
                <a class="nav-item {{ (request()->routeIs('admin.logs.*') || $navSection === 'logs') ? 'active' : '' }}" href="{{ route('admin.logs.index') }}" aria-label="Admin logs">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    <span>{{ __('Logs') }}</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="status-dot"></div>
                <span>{{ __('Admin') }}</span>
            </div>
            <form class="sidebar-locale" action="{{ route('locale.update') }}" method="POST">
                @csrf
                <button type="submit" name="locale" value="en" class="btn ghost btn-small{{ app()->getLocale() === 'en' ? ' active' : '' }}">{{ __('English') }}</button>
                <button type="submit" name="locale" value="ha" class="btn ghost btn-small{{ app()->getLocale() === 'ha' ? ' active' : '' }}">{{ __('Hausa') }}</button>
            </form>
        </aside>

        @yield('main')
    </div>

    <div class="modal" id="confirm-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="confirm-title">{{ __('Confirm Action') }}</h3>
                <button class="icon-close" type="button" data-confirm-close aria-label="{{ __('Close dialog') }}">&times;</button>
            </div>
            <p class="modal-message" data-confirm-message>{{ __('Are you sure?') }}</p>
            <div class="modal-actions">
                <button class="btn ghost" type="button" data-confirm-no>{{ __('Cancel') }}</button>
                <button class="btn primary" type="button" data-confirm-yes>{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/admin-actions.js') }}"></script>
    <script src="{{ asset('assets/js/admin-dashboard.js') }}"></script>
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
    <script src="{{ asset('assets/vendor/quill/quill.js') }}"></script>
    <script src="{{ asset('assets/js/editor.js') }}"></script>
    @stack('scripts')
</body>
</html>
