<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BridgeBox</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
    <style>
        /* ── Mode switch banner ── */
        .mode-switch-banner {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 50;
        }

        .mode-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 999px;
            border: 1px solid rgba(61, 111, 214, 0.22);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s, color 0.2s;
            box-shadow: 0 2px 12px rgba(16, 27, 52, 0.08);
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
        }

        .mode-switch-btn:hover {
            border-color: rgba(61, 111, 214, 0.5);
            color: var(--accent);
            box-shadow: 0 4px 18px rgba(61, 111, 214, 0.14);
        }

        .mode-switch-btn svg {
            flex-shrink: 0;
            opacity: 0.7;
        }

        /* ── Confirm modal ── */
        .switch-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 40, 0.48);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 200;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.22s ease;
        }

        .switch-modal-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        .switch-modal-card {
            width: min(440px, 100%);
            background: var(--panel);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 24px 48px rgba(16, 27, 52, 0.22);
            display: flex;
            flex-direction: column;
            gap: 14px;
            transform: translateY(12px);
            transition: transform 0.22s ease;
        }

        .switch-modal-overlay.open .switch-modal-card {
            transform: translateY(0);
        }

        .switch-modal-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(61, 111, 214, 0.1);
            display: grid;
            place-items: center;
            color: var(--accent);
        }

        .switch-modal-card h2 {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            color: var(--ink);
            margin: 0;
        }

        .switch-modal-card p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
            margin: 0;
        }

        .switch-modal-note {
            background: rgba(61, 111, 214, 0.07);
            border: 1px solid rgba(61, 111, 214, 0.15);
            border-radius: 10px;
            padding: 10px 13px;
            font-size: 12px;
            color: var(--accent);
        }

        .switch-modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }

        .switch-modal-actions .btn-cancel {
            flex: 1;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid var(--border, #d6dfed);
            background: transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: background 0.15s;
        }

        .switch-modal-actions .btn-cancel:hover { background: #f4f7fb; }

        .switch-modal-actions .btn-confirm {
            flex: 2;
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #3c5f9d 0%, #2d78b0 100%);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .switch-modal-actions .btn-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(39, 58, 98, 0.2);
        }

        .status-toast {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--ink);
            color: #fff;
            font-size: 13px;
            padding: 9px 18px;
            border-radius: 999px;
            z-index: 300;
            animation: toastFade 3s ease forwards;
        }

        @keyframes toastFade {
            0%   { opacity: 0; transform: translateX(-50%) translateY(6px); }
            15%  { opacity: 1; transform: translateX(-50%) translateY(0); }
            75%  { opacity: 1; }
            100% { opacity: 0; }
        }
    </style>
</head>

@if ($installMode->isGeneric())
<body class="auth-body generic-landing">
    <div class="auth-shell">
        <div class="role-panel" style="text-align:center;">
            <div class="logo-stack" style="margin-bottom:20px;">
                <img class="brand-logo" src="{{ asset('assets/images/bridgebox.png') }}" alt="BridgeBox logo">
            </div>
            <p class="eyebrow">{{ __('BridgeBox') }}</p>
            <h1 style="margin-bottom:8px;">{{ __('Learning Library') }}</h1>
            <p class="subtext" style="margin-bottom:32px;">{{ __('Browse and explore available courses - no account needed.') }}</p>
            <a class="btn primary" href="{{ route('courses.index') }}" style="display:inline-block;min-width:180px;">{{ __('Browse Courses') }}</a>
            <p style="margin-top:28px;font-size:12px;opacity:0.5;">
                <a href="{{ route('login', ['role' => 'admin']) }}" style="color:inherit;">{{ __('Admin login') }}</a>
            </p>
        </div>
    </div>

    {{-- Mode switch trigger (only shown to logged-in admin) --}}
    @auth
        @if(auth()->user()->role === 'admin')
        <div class="mode-switch-banner">
            <button class="mode-switch-btn" id="switchModeBtn" type="button" aria-haspopup="dialog">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2 9l1 1v7a1 1 0 001 1h5v-5h6v5h5a1 1 0 001-1V10l1-1-10-6-10 6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
                {{ __('Switch to School mode') }}
            </button>
        </div>

        <!-- Confirm switch modal -->
        <div class="switch-modal-overlay" id="switchModalOverlay" role="dialog" aria-modal="true" aria-labelledby="switchModalTitle">
            <div class="switch-modal-card">
                <div class="switch-modal-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M2 9l1 1v7a1 1 0 001 1h5v-5h6v5h5a1 1 0 001-1V10l1-1-10-6-10 6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </div>
                <h2 id="switchModalTitle">{{ __('Switch to School mode?') }}</h2>
                <p>{{ __('This will switch BridgeBox to School mode — you\'ll get access to classes, sections, and the full academic setup.') }}</p>
                <div class="switch-modal-note">
                    {{ __('Your existing data and admin account will be kept. You can switch back at any time.') }}
                </div>
                <div class="switch-modal-actions">
                    <button class="btn-cancel" id="switchCancelBtn" type="button">{{ __('Cancel') }}</button>
                    <form method="POST" action="{{ route('switch.mode') }}" style="flex:2;display:flex;">
                        @csrf
                        <input type="hidden" name="mode" value="school">
                        <button class="btn-confirm" type="submit" style="width:100%;">{{ __('Yes, switch to School') }}</button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endauth

    <script src="{{ asset('assets/js/offline.js') }}"></script>
    <script>
        (function () {
            const btn = document.getElementById('switchModeBtn');
            const overlay = document.getElementById('switchModalOverlay');
            const cancelBtn = document.getElementById('switchCancelBtn');
            if (!btn || !overlay) return;
            btn.addEventListener('click', () => overlay.classList.add('open'));
            cancelBtn.addEventListener('click', () => overlay.classList.remove('open'));
            overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('open'); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') overlay.classList.remove('open'); });
        })();
    </script>
    @if(session('status'))
    <div class="status-toast" role="status">{{ session('status') }}</div>
    @endif
</body>

@else
<body class="auth-body" data-screen="splash">
    <div class="auth-shell">
        <section class="screen splash" id="splash" aria-label="BridgeBox loading screen">
            <div class="logo-stack" aria-hidden="true">
                <img class="brand-logo" src="{{ asset('assets/images/bridgebox.png') }}" alt="BridgeBox logo">
            </div>
        </section>

        <section class="screen role" id="role" aria-label="Role selection">
            <div class="role-panel">
                <p class="eyebrow">{{ __('BridgeBox Access') }}</p>
                <h1>{{ __('What do you want to login as') }}</h1>
                <p class="subtext">{{ __('Choose a role to continue to your secure workspace.') }}</p>

                <div class="role-options">
                    <a class="role-option admin" href="{{ route('login', ['role' => 'admin']) }}">
                        <div class="role-icon">A</div>
                        <div class="role-text">
                            <span class="role-title">{{ __('Admin') }}</span>
                            <span class="role-sub">{{ __('Manage access and oversight') }}</span>
                        </div>
                        <span class="role-arrow">→</span>
                    </a>
                    <a class="role-option teacher" href="{{ route('login', ['role' => 'teacher']) }}">
                        <div class="role-icon">T</div>
                        <div class="role-text">
                            <span class="role-title">{{ __('Teacher') }}</span>
                            <span class="role-sub">{{ __('Prepare lessons and share') }}</span>
                        </div>
                        <span class="role-arrow">→</span>
                    </a>
                    <a class="role-option student" href="{{ route('login', ['role' => 'student']) }}">
                        <div class="role-icon">S</div>
                        <div class="role-text">
                            <span class="role-title">{{ __('Student') }}</span>
                            <span class="role-sub">{{ __('Explore content and learn') }}</span>
                        </div>
                        <span class="role-arrow">→</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

    {{-- Mode switch trigger (admin only, shown after role panel animates in) --}}
    @auth
        @if(auth()->user()->role === 'admin')
        <div class="mode-switch-banner">
            <button class="mode-switch-btn" id="switchModeBtn" type="button" aria-haspopup="dialog">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 6.5A2.5 2.5 0 015.5 4H10v12H5.5A2.5 2.5 0 013 13.5v-7z" fill="currentColor" opacity="0.8"/>
                    <path d="M14 4h4.5A2.5 2.5 0 0121 6.5v7A2.5 2.5 0 0118.5 16H14V4z" fill="currentColor" opacity="0.6"/>
                </svg>
                {{ __('Switch to Generic Learning mode') }}
            </button>
        </div>

        <!-- Confirm switch modal -->
        <div class="switch-modal-overlay" id="switchModalOverlay" role="dialog" aria-modal="true" aria-labelledby="switchModalTitle">
            <div class="switch-modal-card">
                <div class="switch-modal-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 6.5A2.5 2.5 0 015.5 4H10v12H5.5A2.5 2.5 0 013 13.5v-7z" fill="currentColor" opacity="0.8"/><path d="M14 4h4.5A2.5 2.5 0 0121 6.5v7A2.5 2.5 0 0118.5 16H14V4z" fill="currentColor" opacity="0.6"/></svg>
                </div>
                <h2 id="switchModalTitle">{{ __('Switch to Generic Learning mode?') }}</h2>
                <p>{{ __('This will switch BridgeBox to Generic Learning mode — the landing page becomes a public course browser, no student or teacher login required.') }}</p>
                <div class="switch-modal-note">
                    {{ __('Your existing data and admin account will be kept. You can switch back to School mode at any time.') }}
                </div>
                <div class="switch-modal-actions">
                    <button class="btn-cancel" id="switchCancelBtn" type="button">{{ __('Cancel') }}</button>
                    <form method="POST" action="{{ route('switch.mode') }}" style="flex:2;display:flex;">
                        @csrf
                        <input type="hidden" name="mode" value="generic">
                        <button class="btn-confirm" type="submit" style="width:100%;">{{ __('Yes, switch to Generic') }}</button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endauth

    <script src="{{ asset('assets/js/landing.js') }}"></script>
    <script src="{{ asset('assets/js/offline.js') }}"></script>
    <script>
        (function () {
            const btn = document.getElementById('switchModeBtn');
            const overlay = document.getElementById('switchModalOverlay');
            const cancelBtn = document.getElementById('switchCancelBtn');
            if (!btn || !overlay) return;
            btn.addEventListener('click', () => overlay.classList.add('open'));
            cancelBtn.addEventListener('click', () => overlay.classList.remove('open'));
            overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('open'); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') overlay.classList.remove('open'); });
        })();
    </script>
    @if(session('status'))
    <div class="status-toast" role="status">{{ session('status') }}</div>
    @endif
</body>
@endif
</html>
