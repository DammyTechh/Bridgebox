@extends('admin.layout')

@section('title', 'Student Created')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin User Management') }}</p>
                <h1>{{ __('Student Created') }}</h1>
                <p class="subtext">{{ __('The student account has been created successfully.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.users.students.index') }}">{{ __('Back to Students') }}</a>
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
                        <p>{{ $student->name }}</p>
                        <span>{{ $student->email }}</span>
                    </div>
                </div>

                @php($className = $student->schoolClass?->name ?? $profile?->class)
                @if ($className)
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Class') }}</p>
                            <span>{{ $className }}</span>
                        </div>
                    </div>
                @endif
                @if ($profile?->department)
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Department') }}</p>
                            <span>{{ $profile->department }}</span>
                        </div>
                    </div>
                @endif
                @if ($profile?->admission_id)
                    <div class="item">
                        <div class="item-info">
                            <p>{{ __('Admission ID') }}</p>
                            <span>{{ $profile->admission_id }}</span>
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
