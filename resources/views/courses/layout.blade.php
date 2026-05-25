<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Courses') - BridgeBox</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/courses.css') }}">
</head>
<body class="courses-body">

    <nav class="courses-nav" aria-label="Site navigation">
        <a class="nav-brand" href="{{ route('courses.index') }}">
            <img src="{{ asset('assets/images/bridgebox.png') }}" alt="BridgeBox">
        </a>
        <div class="nav-right">
            <a class="nav-admin-link" href="{{ route('login', ['role' => 'admin']) }}">{{ __('Admin') }}</a>
        </div>
    </nav>

    <div class="courses-container">
        @yield('content')
    </div>

    <script src="{{ asset('assets/js/offline.js') }}"></script>
    @stack('scripts')
</body>
</html>
