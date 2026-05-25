<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BridgeBox - School Installer</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
</head>
<body class="auth-body installer-body" data-screen="role">
    <div class="auth-shell installer-shell">
        <section class="screen role" id="role" aria-label="School installer">
            <div class="login-screen">
                <div class="login-panel">
                    <div class="login-header">
                        <span class="role-pill">School</span>
                        <h1>BridgeBox Setup</h1>
                        <p class="subtext">Create the admin account and choose which class sections to seed.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form class="login-form" action="{{ route('install.store') }}" method="post">
                        @csrf
                        <div class="field">
                            <label for="name">Admin name</label>
                            <input id="name" name="name" type="text" placeholder="Full name" value="{{ old('name') }}" required>
                        </div>

                        <div class="field">
                            <label for="email">Admin email</label>
                            <input id="email" name="email" type="email" placeholder="admin@bridgebox.local" value="{{ old('email') }}" required>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required>
                        </div>

                        <div class="field">
                            <label for="password_confirmation">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        </div>

                        <div class="field">
                            <label>Select class sections to create</label>
                            <div class="checkbox-grid">
                                @foreach ($sections as $section)
                                    <label class="checkbox">
                                        <input type="checkbox" name="sections[]" value="{{ $section['slug'] }}">
                                        <span>{{ $section['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="field">
                            <label>Optional</label>
                            <label class="checkbox">
                                <input type="checkbox" name="demo_mode" value="1" {{ old('demo_mode') ? 'checked' : '' }}>
                                <span>Seed demo data</span>
                            </label>
                        </div>

                        <button class="btn primary" type="submit">Finish Installation</button>
                    </form>

                    <p style="margin-top:16px;text-align:center;font-size:13px;">
                        <a href="{{ route('install.show') }}" style="color:var(--accent,#3b6fd4);">← Back to mode selection</a>
                    </p>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
