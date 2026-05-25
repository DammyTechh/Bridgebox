<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>BridgeBox - Generic Installer</title>
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
</head>
<body class="auth-body installer-body" data-screen="role">
<div class="auth-shell installer-shell">
    <section class="screen role" id="role" aria-label="Generic installer">
        <div class="login-screen">
            <div class="login-panel">
                <div class="login-header">
                    <span class="role-pill">Generic</span>
                    <h1>Generic Learning Setup</h1>
                    <p class="subtext">This installer will seed demo content and create an admin account for generic learning mode.</p>
                </div>

                <form class="login-form" action="{{ route('install.generic.store') }}" method="post" id="generic-install-form">
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif
                    <div class="field">
                        <label for="name">Admin name</label>
                        <input id="name" name="name" type="text" placeholder="Full name" required>
                    </div>

                    <div class="field">
                        <label for="email">Admin email</label>
                        <input id="email" name="email" type="email" placeholder="admin@example.com" required>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <div class="field">
                        <label>Seed demo courses</label>
                        <label class="checkbox">
                            <input type="checkbox" name="seed_demo" value="1" checked>
                            <span>Install demo courses (Digital Literacy, Basic Computing)</span>
                        </label>
                    </div>

                    <div class="field">
                        <label>Import source (optional)</label>
                        <input id="import_source" type="text" name="import_source" placeholder="Google Drive URL or remote server URL">
                    </div>

                    <div style="display:flex;gap:12px;align-items:center;">
                        <button class="btn primary" type="submit">Start Generic Install</button>
                        <div id="install-spinner" style="display:none;">Installing... please wait</div>
                    </div>
                </form>
                <p style="margin-top:16px;text-align:center;font-size:13px;">
                    <a href="{{ route('install.show') }}" style="color:var(--accent,#3b6fd4);">← Back to mode selection</a>
                </p>
                <script>
                    (function(){
                        const form = document.getElementById('generic-install-form');
                        const spinner = document.getElementById('install-spinner');
                        if (!form) return;
                        form.addEventListener('submit', () => {
                            spinner.style.display = 'block';
                        });
                    })();
                </script>
            </div>
        </div>
    </section>
</div>
</body>
</html>