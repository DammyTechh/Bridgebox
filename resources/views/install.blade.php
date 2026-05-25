<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BridgeBox Installer</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
</head>
<body class="auth-body installer-body" data-screen="{{ request()->query('mode') === 'school' ? 'role' : 'splash' }}">
    <div class="auth-shell installer-shell">
        <section class="screen splash" id="splash" aria-label="BridgeBox loading screen">
            <div class="logo-stack" aria-hidden="true">
                <img class="brand-logo" src="{{ asset('assets/images/bridgebox.png') }}" alt="BridgeBox logo">
            </div>
        </section>

        <section class="screen choice" id="choice" aria-label="Installer choice">
            <div class="role-panel">
                <div class="login-header">
                    <span class="role-pill">Mode</span>
                    <h1>Choose usage</h1>
                    <p class="subtext">Select whether this device will be used for a school or for generic learning.</p>
                </div>

                <div class="role-options" style="margin-top:18px;">
                    <a class="role-option school" href="{{ route('install.show', ['mode' => 'school']) }}" aria-label="Install for school">
                        <div class="role-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
                                <path d="M12 3L2 9l1 1v7a1 1 0 001 1h5v-5h6v5h5a1 1 0 001-1V10l1-1-10-6z" fill="currentColor" opacity="0.95"/>
                                <path d="M9 13h6v4H9v-4z" fill="#fff" opacity="0.15"/>
                            </svg>
                        </div>
                        <div class="role-text">
                            <div class="role-title">School</div>
                            <div class="role-sub">Complete school installer - sections, classes and academic seeding tailored for schools.</div>
                        </div>
                        <div class="role-arrow">›</div>
                    </a>

                    <a class="role-option generic" href="{{ route('install.generic.show') }}" aria-label="Install for generic learning">
                        <div class="role-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
                                <path d="M3 6.5A2.5 2.5 0 015.5 4H10v12H5.5A2.5 2.5 0 013 13.5v-7z" fill="currentColor" opacity="0.95"/>
                                <path d="M14 4h4.5A2.5 2.5 0 0121 6.5v11A2.5 2.5 0 0118.5 20H14V4z" fill="currentColor" opacity="0.85"/>
                                <path d="M6 6h10v2H6z" fill="#fff" opacity="0.12"/>
                            </svg>
                        </div>
                        <div class="role-text">
                            <div class="role-title">Generic learning</div>
                            <div class="role-sub">Lightweight mode for organisations or public devices - demo courses and import content quickly.</div>
                        </div>
                        <div class="role-arrow">›</div>
                    </a>
                </div>
            </div>
        </section>

        <section class="screen role" id="role" aria-label="Installer">
            <div class="login-screen">
                <div class="login-panel">
                    <div class="login-header">
                        <span class="role-pill">Installer</span>
                        <h1>BridgeBox Setup</h1>
                        <p class="subtext">This runs once to create the admin account and seed default class sections.</p>
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
                                    @php($checked = in_array($section['slug'], old('sections', array_column($sections, 'slug')), true))
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
                </div>
            </div>
        </section>
    </div>

    <script src="{{ asset('assets/js/landing.js') }}"></script>
</body>
</html>
