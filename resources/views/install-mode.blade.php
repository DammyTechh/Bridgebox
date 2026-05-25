<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BridgeBox Installer</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
</head>
<body class="auth-body installer-body" data-screen="choice">
    <div class="auth-shell installer-shell">
        <section class="screen choice" id="choice" aria-label="Installer mode selection">
            <div class="role-panel">
                <div class="login-header">
                    <div class="logo-stack" style="margin-bottom:12px;">
                        <img class="brand-logo" src="{{ asset('assets/images/bridgebox.png') }}" alt="BridgeBox logo">
                    </div>
                    <span class="role-pill">Install</span>
                    <h1>Choose a mode</h1>
                    <p class="subtext">Select how this installation will be used.</p>
                </div>

                <div class="role-options" style="margin-top:24px;">
                    <a class="role-option school" href="{{ route('install.school.show') }}" aria-label="Install for school">
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
    </div>
</body>
</html>
