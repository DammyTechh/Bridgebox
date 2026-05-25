<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $role }} Login | BridgeBox</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
</head>
<body class="auth-body">
    <div class="auth-shell">
        <section class="login-screen">
            <div class="login-panel">
                <a class="back-link" href="{{ route('landing') }}">← Back to roles</a>

                <div class="login-header">
                    <span class="role-pill">{{ $role }}</span>
                    <h1>{{ $role }} Login</h1>
                    <p class="subtext">{{ $subtitle }}</p>
                </div>

                <div class="alert" id="login-error" @if(!session('error') && !$errors->any()) hidden @endif>
                    {{ session('error') ?? ($errors->first() ?? '') }}
                </div>

                <form class="login-form" id="login-form" action="{{ route('login.submit', ['role' => $roleKey]) }}" method="post">
                    @csrf
                    <div class="field">
                        <label for="email">{{ __('Email or username') }}</label>
                        <input id="email" name="identifier" type="text" placeholder="{{ __('name@school.edu') }}" autocomplete="username" value="{{ old('identifier') }}">
                    </div>

                    <div class="field">
                        <label for="password">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" placeholder="{{ __('Enter your password') }}" autocomplete="current-password">
                    </div>

                    <button class="btn primary" type="submit">{{ __('Login') }}</button>
                    <button class="forgot-link" type="button" data-modal-open="forgot-modal">{{ __('Forgot password?') }}</button>
                </form>
            </div>
        </section>
    </div>

    <div class="modal" id="forgot-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="forgot-title">
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="forgot-title">{{ __('Offline Password Help') }}</h2>
                <button class="icon-close" type="button" data-modal-close="forgot-modal" aria-label="{{ __('Close dialog') }}">×</button>
            </div>
            <p>{{ __('BridgeBox is designed to work offline, so password resets happen locally.') }}</p>
            <ul class="modal-list">
                <li>{{ __('Ask your school admin for a local reset code.') }}</li>
                <li>{{ __('If you are an admin, use the device settings panel to issue a reset.') }}</li>
                <li>{{ __('If neither is available, wait until connectivity returns to sync a reset.') }}</li>
            </ul>
            <button class="btn primary" type="button" data-modal-close="forgot-modal">{{ __('Got it') }}</button>
        </div>
    </div>

    <script src="{{ asset('assets/js/auth.js') }}"></script>
    <script src="{{ asset('assets/js/login.js') }}"></script>
    <script src="{{ asset('assets/js/offline.js') }}"></script>
</body>
</html>
