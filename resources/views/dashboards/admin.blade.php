@extends('admin.layout')

@section('title', __('Admin Dashboard'))

@php
    $status = $status ?? [];
    $sudoAllowed = $sudoAllowed ?? false;
    $sudoBlocked = ! $sudoAllowed;
    $serverRunning = ($status['server'] ?? '') === 'Running';
    $hotspotLabel = strtolower((string) ($status['hotspot'] ?? ''));
    $hotspotOn = str_starts_with($hotspotLabel, 'on');
    $stats = $stats ?? [];
    $sections = $sections ?? collect();
    $sectionAccents = ['#4a7bd1', '#56c1a7', '#f2b84b', '#5b8de3', '#f08b5a', '#e56b6f'];
@endphp

@section('main')
    <main class="main" data-refresh-url="{{ route('dashboard.admin.status') }}" data-refresh-interval="1000" data-auto-refresh="on">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin Control Room') }}</p>
                <h1>{{ __('Hello,') }} {{ auth()->user()->name ?? __('Admin') }}.</h1>
                <p class="subtext">
                    @if ($installMode->isGeneric())
                        {{ __('Manage courses, topics and lessons for the learning library.') }}
                    @else
                        {{ __('Monitor core services and manage system controls.') }}
                    @endif
                </p>
            </div>
            <div class="actions">
                @if ($installMode->isSchool())
                <a class="btn ghost" href="{{ route('admin.users.teachers.index') }}">{{ __('Teachers') }}</a>
                <a class="btn ghost" href="{{ route('admin.users.students.index') }}">{{ __('Students') }}</a>
                <a class="btn ghost" href="{{ route('admin.classes.index') }}">{{ __('Classes') }}</a>
                @endif
                @if ($installMode->isGeneric())
                <a class="btn ghost" href="{{ route('admin.subjects.index') }}">{{ __('Courses') }}</a>
                <a class="btn ghost" href="{{ route('courses.index') }}">{{ __('View Public Site') }}</a>
                @endif
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if (session('action_message'))
            <div class="alert alert-dismissible {{ session('action_status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="4000">
                <span data-alert-message>{{ session('action_message') }}</span>
                <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="Dismiss alert">&times;</button>
            </div>
        @endif

        <div class="alert alert-dismissible" id="action-alert" role="status" hidden>
            <span data-alert-message></span>
            <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="Dismiss alert">&times;</button>
        </div>

        <section class="quick-tabs">
            <div class="tab" style="--accent: #4a7bd1; --d: 0.05s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-server" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Server Status') }}</p>
                    <span data-status="server">{{ $status['server'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab {{ $hotspotOn ? 'tab--on' : 'tab--off' }}" style="--accent: {{ $hotspotOn ? '#56c1a7' : '#e56b6f' }}; --d: 0.1s;" data-hotspot-tab>
                <div class="tab-icon">
                    <i class="fa-solid fa-wifi" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Hotspot Status') }}</p>
                    <span data-status="hotspot" class="hotspot-badge {{ $hotspotOn ? 'hotspot-on' : 'hotspot-off' }}">{{ $status['hotspot'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #f2b84b; --d: 0.15s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-network-wired" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Connected Devices') }}</p>
                    <span data-status="devices">{{ $status['devices'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #56c1a7; --d: 0.2s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-heart-pulse" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('App Health') }}</p>
                    <span data-status="app_health">{{ $status['app_health'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #5b8de3; --d: 0.25s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-hard-drive" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Storage') }}</p>
                    <span data-status="storage">{{ $status['storage'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #f08b5a; --d: 0.3s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-battery-three-quarters" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Battery') }}</p>
                    <span data-status="power">{{ $status['power'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #3bb98d; --d: 0.35s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Uptime') }}</p>
                    <span data-status="uptime" data-uptime-seconds="{{ $status['uptime_seconds'] ?? '' }}">{{ $status['uptime'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #e45757; --d: 0.4s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Last Update') }}</p>
                    <span data-status="last_update">{{ $status['last_update'] ?? 'Unknown' }}</span>
                </div>
            </div>
        </section>

        {{-- ============================================================
             USB CONTENT IMPORT
             Replaces the entire previous "Admin Controls" panel
             (Hotspot Control toggle + Reboot/Shutdown actions) and
             the old SSID/password card. Power actions remain accessible
             through the sidebar / system menu if needed.
             ============================================================ --}}
        @include('partials.usb-import-panel', ['variant' => 'admin', 'showLibrary' => true])

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Academic Overview') }}</h4>
                <span class="badge blue">{{ __('Counts') }}</span>
            </div>
            <div class="panel-body">
                <section class="quick-tabs" style="padding:0;">
                    @if ($installMode->isSchool())
                    <a class="tab" style="--accent: #4a7bd1; --d: 0.05s;" href="{{ route('admin.classes.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-people-roof" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Classes') }}</p>
                            <span>{{ $stats['classes'] ?? 0 }}</span>
                        </div>
                    </a>
                    <a class="tab" style="--accent: #56c1a7; --d: 0.1s;" href="{{ route('admin.users.teachers.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Teachers') }}</p>
                            <span>{{ $stats['teachers'] ?? 0 }}</span>
                        </div>
                    </a>
                    <a class="tab" style="--accent: #e56b6f; --d: 0.15s;" href="{{ route('admin.users.students.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Students') }}</p>
                            <span>{{ $stats['students'] ?? 0 }}</span>
                        </div>
                    </a>
                    @endif
                    <a class="tab" style="--accent: #f2b84b; --d: 0.2s;" href="{{ route('admin.subjects.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-book" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ $installMode->isGeneric() ? __('Courses') : __('Subjects') }}</p>
                            <span>{{ $stats['subjects'] ?? 0 }}</span>
                        </div>
                    </a>
                    <a class="tab" style="--accent: #5b8de3; --d: 0.25s;" href="{{ route('admin.topics.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Topics') }}</p>
                            <span>{{ $stats['topics'] ?? 0 }}</span>
                        </div>
                    </a>
                    <a class="tab" style="--accent: #3bb98d; --d: 0.3s;" href="{{ route('admin.topics.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Lessons') }}</p>
                            <span>{{ $stats['lessons'] ?? 0 }}</span>
                        </div>
                    </a>
                    <a class="tab" style="--accent: #f08b5a; --d: 0.35s;" href="{{ route('admin.assignments.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Assignments') }}</p>
                            <span>{{ $stats['assignments'] ?? 0 }}</span>
                        </div>
                    </a>
                    <a class="tab" style="--accent: #6d82f6; --d: 0.4s;" href="{{ route('admin.quizzes.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Quizzes') }}</p>
                            <span>{{ $stats['quizzes'] ?? 0 }}</span>
                        </div>
                    </a>
                    <a class="tab" style="--accent: #e45757; --d: 0.45s;" href="{{ route('admin.exams.index') }}">
                        <div class="tab-icon">
                            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p>{{ __('Exams') }}</p>
                            <span>{{ $stats['exams'] ?? 0 }}</span>
                        </div>
                    </a>
                </section>
            </div>
        </section>

        @if ($installMode->isSchool())
        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Sections Overview') }}</h4>
                <span class="badge gold">{{ __('Sections') }}</span>
            </div>
            <div class="panel-body">
                <section class="quick-tabs" style="padding:0;">
                    @forelse ($sections as $section)
                        <a class="tab" style="--accent: {{ $sectionAccents[$loop->index % count($sectionAccents)] }}; --d: {{ 0.05 + ($loop->index * 0.05) }}s;" href="{{ route('admin.classes.index', ['section_id' => $section->id]) }}">
                            <div class="tab-icon">
                                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p>{{ $section->name }}</p>
                                <span>{{ $section->classes_count }} classes · {{ $section->subjects_count }} subjects</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-muted">{{ __('No sections configured yet.') }}</p>
                    @endforelse
                </section>
            </div>
        </section>
        @endif

    </main>
@endsection
