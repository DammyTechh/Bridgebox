@extends('admin.layout')

@section('title', __('Teacher Created'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin User Management') }}</p>
                <h1>{{ __('Teacher Created') }}</h1>
                <p class="subtext">{{ __('The teacher account has been created successfully.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.users.teachers.index') }}">{{ __('Back to Teachers') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Account Summary') }}</h4>
                <span class="badge blue">{{ __('New') }}</span>
            </div>
            <div class="panel-body">
                <div class="item">
                    <div class="item-info">
                        <p>{{ $teacher->name }}</p>
                        <span>{{ $teacher->email }}</span>
                    </div>
                </div>

                <div class="item">
                    <div class="item-info">
                        <p>{{ __('Phone') }}</p>
                        <span>{{ $teacher->phone ?: '-' }}</span>
                    </div>
                </div>

                @if ($teacher->schoolClass?->name)
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Class') }}</p>
                            <span>{{ $teacher->schoolClass->name }}</span>
                        </div>
                    </div>
                @endif

                @if ($passwordMode === 'auto' && $generatedPassword)
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Generated Password') }}</p>
                            <span class="password-note">{{ __('Shown once. Copy and share securely.') }}</span>
                        </div>
                        <div class="password-box" data-copy-target>{{ $generatedPassword }}</div>
                        <button class="btn ghost btn-small" type="button" data-copy-button>{{ __('Copy') }}</button>
                    </div>
                @else
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Password') }}</p>
                            <span>{{ __('Set manually during creation.') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const copyButton = document.querySelector('[data-copy-button]');
        const copyTarget = document.querySelector('[data-copy-target]');
        if (copyButton && copyTarget) {
            copyButton.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(copyTarget.textContent.trim());
                    copyButton.textContent = 'Copied';
                    setTimeout(() => {
                        copyButton.textContent = 'Copy';
                    }, 1500);
                } catch (error) {
                    copyButton.textContent = 'Copy failed';
                }
            });
        }
    </script>
@endpush
