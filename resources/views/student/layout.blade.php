<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BridgeBox Student')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
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
                <a class="nav-item {{ request()->routeIs('dashboard.student') ? 'active' : '' }}" href="{{ route('dashboard.student') }}" aria-label="Student dashboard">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    <span>{{ __('Home') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.subjects.*') ? 'active' : '' }}" href="{{ route('student.subjects.index') }}" aria-label="Subjects">
                    <i class="fa-solid fa-book" aria-hidden="true"></i>
                    <span>{{ __('Subjects') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.topics.*') ? 'active' : '' }}" href="{{ route('student.topics.index') }}" aria-label="Topics">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>{{ __('Topics') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.lessons.*') ? 'active' : '' }}" href="{{ route('student.lessons.index') }}" aria-label="Lessons">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                    <span>{{ __('Lessons') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}" href="{{ route('student.assignments.index') }}" aria-label="Assignments">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    <span>{{ __('Assignments') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.progress.*') ? 'active' : '' }}" href="{{ route('student.progress.index') }}" aria-label="Progress">
                    <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                    <span>{{ __('Progress') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.profile.*') ? 'active' : '' }}" href="{{ route('student.profile.show') }}" aria-label="My Profile">
                    <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                    <span>{{ __('Profile') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.quizzes.*') ? 'active' : '' }}" href="{{ route('student.quizzes.index') }}" aria-label="Quizzes">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                    <span>{{ __('Quizzes') }}</span>
                </a>
                <a class="nav-item {{ request()->routeIs('student.exams.*') ? 'active' : '' }}" href="{{ route('student.exams.index') }}" aria-label="Exams">
                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                    <span>{{ __('Exams') }}</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="status-dot"></div>
                <span>{{ __('Student') }}</span>
            </div>
            <form class="sidebar-locale" action="{{ route('locale.update') }}" method="POST">
                @csrf
                <button type="submit" name="locale" value="en" class="btn ghost btn-small{{ app()->getLocale() === 'en' ? ' active' : '' }}">{{ __('English') }}</button>
                <button type="submit" name="locale" value="ha" class="btn ghost btn-small{{ app()->getLocale() === 'ha' ? ' active' : '' }}">{{ __('Hausa') }}</button>
            </form>
            @if (session('impersonator_id'))
                <form class="sidebar-logout" action="{{ route('impersonate.stop') }}" method="post">
                    @csrf
                    <button class="btn ghost btn-small" type="submit">{{ __('Return to Admin') }}</button>
                </form>
            @endif
            <form class="sidebar-logout" action="{{ route('logout') }}" method="post">
                @csrf
                <button class="btn ghost btn-small" type="submit">{{ __('Logout') }}</button>
            </form>
        </aside>

        @yield('main')
    </div>

    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
    @stack('scripts')
</body>
</html>
