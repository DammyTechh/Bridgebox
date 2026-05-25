@extends('student.layout')

@section('title', 'My Profile')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ __('My Profile') }}</h1>
                <p class="subtext">{{ __('View and update your personal details.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('dashboard.student') }}">{{ __('Back to Dashboard') }}</a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert-block success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-block error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Profile summary card --}}
        <section class="panel profile-card-panel">
            <div class="profile-card">
                <div class="profile-avatar" aria-hidden="true">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div class="profile-meta">
                    <h2>{{ $student->name }}</h2>
                    <p class="profile-email">{{ $student->email }}</p>
                    <div class="profile-tags">
                        @if ($schoolClass)
                            <span class="badge blue">
                                <i class="fa-solid fa-school" aria-hidden="true"></i>
                                {{ $schoolClass->name }}
                            </span>
                        @endif
                        @if ($schoolClass?->section)
                            <span class="badge green">
                                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                                {{ $schoolClass->section->name }}
                            </span>
                        @endif
                        @if ($profile->admission_id)
                            <span class="badge muted">
                                <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                                {{ $profile->admission_id }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Details grid --}}
        <section class="panel profile-details-panel">
            <div class="panel-header">
                <h4>{{ __('Account Details') }}</h4>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Full name') }}</span>
                        <span class="detail-value">{{ $student->name }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Email address') }}</span>
                        <span class="detail-value">{{ $student->email }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Phone') }}</span>
                        <span class="detail-value">{{ $student->phone ?? '-' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Admission ID') }}</span>
                        <span class="detail-value">{{ $profile->admission_id ?? '-' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Department') }}</span>
                        <span class="detail-value">{{ $profile->department ?? '-' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Class') }}</span>
                        <span class="detail-value">{{ $schoolClass?->name ?? '-' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Section') }}</span>
                        <span class="detail-value">{{ $schoolClass?->section?->name ?? '-' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Account status') }}</span>
                        <span class="detail-value">
                            @if ($student->is_active)
                                <span class="badge green">{{ __('Active') }}</span>
                            @else
                                <span class="badge muted">{{ __('Inactive') }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ __('Member since') }}</span>
                        <span class="detail-value">{{ $student->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Edit form --}}
        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Edit Profile') }}</h4>
            </div>
            <div class="panel-body">
                <form action="{{ route('student.profile.update') }}" method="post" class="profile-form">
                    @csrf

                    <div class="form-row">
                        <div class="form-field">
                            <label for="name">{{ __('Full name') }}</label>
                            <input id="name" name="name" type="text"
                                   value="{{ old('name', $student->name) }}" required>
                        </div>
                        <div class="form-field">
                            <label for="phone">{{ __('Phone') }} <span class="field-optional">({{ __('optional') }})</span></label>
                            <input id="phone" name="phone" type="tel"
                                   value="{{ old('phone', $student->phone) }}"
                                   placeholder="{{ __('e.g. +234 800 000 0000') }}">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Save changes') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
<style>
/* ── Profile card ── */
.profile-card-panel .panel-body,
.profile-card-panel {
    padding: 0;
    overflow: hidden;
}

.profile-card {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 37px 32px;
    background: linear-gradient(135deg, #2e5bb7 0%, #3d6fd6 60%, #4fbf9b 100%);
    border-radius: 18px;
    color: #fff;
}

.profile-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255,255,255,0.25);
    display: grid;
    place-items: center;
    font-size: 28px;
    font-weight: 700;
    flex-shrink: 0;
    letter-spacing: 0;
    backdrop-filter: blur(4px);
    border: 3px solid rgba(255,255,255,0.35);
}

.profile-meta h2 {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 4px;
}

.profile-email {
    opacity: 0.8;
    font-size: 13px;
    margin-bottom: 12px;
}

.profile-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.profile-tags .badge {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.3);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

/* ── Detail grid ── */
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    padding: 4px 0;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted, #5b6b7f);
    font-weight: 600;
}

.detail-value {
    font-size: 15px;
    color: var(--ink, #182636);
    font-weight: 500;
}

/* ── Edit form ── */
.profile-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 4px;
}

.field-optional {
    font-size: 11px;
    color: var(--muted, #5b6b7f);
    font-weight: 400;
}

/* ── Alert blocks ── */
.alert-block {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    margin-bottom: 4px;
}

.alert-block.success {
    background: rgba(79, 191, 155, 0.12);
    color: #1e7a5a;
    border: 1px solid rgba(79, 191, 155, 0.35);
}

.alert-block.error {
    background: rgba(242, 128, 93, 0.12);
    color: #a3472f;
    border: 1px solid rgba(242, 128, 93, 0.35);
}

@media (max-width: 600px) {
    .profile-card {
        flex-direction: column;
        align-items: flex-start;
        padding: 22px 20px;
    }
}
</style>
@endpush
