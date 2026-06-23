<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Courses') — BridgeBox</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/courses.css') }}">
</head>
<body class="courses-body">

    <!-- Top Nav -->
    <nav class="courses-nav" aria-label="Site navigation">
        <a class="nav-brand" href="{{ route('courses.index') }}" aria-label="BridgeBox home">
            <div class="nav-logo-wrap">
                <img src="{{ asset('assets/images/bridgebox.png') }}"
                     alt="BridgeBox"
                     class="nav-logo-img"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="nav-logo-fallback" aria-hidden="true"></div>
            </div>
            <span class="nav-brand-name">BridgeBox</span>
        </a>

        <div class="nav-right">
            <span class="nav-mode-badge" aria-label="Current mode">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 6.5A2.5 2.5 0 015.5 4H10v12H5.5A2.5 2.5 0 013 13.5v-7z" fill="currentColor"/>
                    <path d="M14 4h4.5A2.5 2.5 0 0121 6.5v7A2.5 2.5 0 0118.5 16H14V4z" fill="currentColor" opacity="0.7"/>
                </svg>
                {{ __('Learning Library') }}
            </span>
            <a class="nav-admin-link" href="{{ route('login', ['role' => 'admin']) }}">{{ __('Admin') }}</a>
        </div>
    </nav>

    <!-- Hero banner — pulled from admin topbar style -->
    @hasSection('hero')
        @yield('hero')
    @endif

    <!-- Main content -->
    <div class="courses-container">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="courses-footer" aria-label="Site footer">
        <div class="footer-brand">
            <span class="f-dot" aria-hidden="true"></span>
            BridgeBox
        </div>
        <span class="footer-caption">{{ __('Offline Learning Platform') }}</span>
    </footer>

    <script src="{{ asset('assets/js/offline.js') }}"></script>
    @stack('scripts')
</body>
</html>
