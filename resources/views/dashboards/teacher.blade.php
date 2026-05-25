@extends('teacher.layout')

@section('title', __('Teacher Dashboard'))

@php
    $status      = $status ?? [];
    $hotspotLabel = strtolower((string) ($status['hotspot'] ?? ''));
    $hotspotOn    = str_starts_with($hotspotLabel, 'on');
@endphp

@section('main')
    <main class="main teacher-dashboard"
          data-refresh-url="{{ route('dashboard.admin.status') }}"
          data-refresh-interval="3000"
          data-auto-refresh="on">
        {{-- ── System status tiles ─────────────────────────────────────── --}}
        <section class="quick-tabs">
            <div class="tab {{ $hotspotOn ? 'tab--on' : 'tab--off' }}" style="--accent: {{ $hotspotOn ? '#56c1a7' : '#e56b6f' }}; --d: 0.05s;" data-hotspot-tab>
                <div class="tab-icon">
                    <i class="fa-solid fa-wifi" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Hotspot Status') }}</p>
                    <span data-status="hotspot" class="hotspot-badge {{ $hotspotOn ? 'hotspot-on' : 'hotspot-off' }}">
                        {{ $status['hotspot'] ?? 'Unknown' }}
                    </span>
                </div>
            </div>
            <div class="tab" style="--accent: #f2b84b; --d: 0.1s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-network-wired" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Connected Devices') }}</p>
                    <span data-status="devices">{{ $status['devices'] ?? '0' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #56c1a7; --d: 0.15s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-battery-three-quarters" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Battery') }}</p>
                    <span data-status="power">{{ $status['power'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="tab" style="--accent: #5b8de3; --d: 0.2s;">
                <div class="tab-icon">
                    <i class="fa-solid fa-hard-drive" aria-hidden="true"></i>
                </div>
                <div>
                    <p>{{ __('Storage') }}</p>
                    <span data-status="storage">{{ $status['storage'] ?? 'Unknown' }}</span>
                </div>
            </div>
        </section>

        <section class="teacher-hero">
            <div class="teacher-hero-content">
                <p class="eyebrow">{{ __('Teacher Console') }}</p>
                <h1>{{ __('Welcome back,') }} {{ auth()->user()->name ?? __('Teacher') }}.</h1>
                <p class="subtext" style="color: black;">{{ __('Manage students, lessons, and assessments with clear visibility.') }}</p>

                <div class="teacher-hero-actions">
                    <a class="btn primary" href="{{ route('teacher.assignments.create') }}">{{ __('New Assignment') }}</a>
                    <a class="btn ghost" href="{{ route('teacher.quizzes.create') }}">{{ __('New Quiz') }}</a>
                    <a class="btn ghost" href="{{ route('teacher.exams.create') }}">{{ __('New Exam') }}</a>
                    <a class="btn ghost" href="{{ route('teacher.topics.create') }}">{{ __('Add Topic') }}</a>
                </div>
            </div>
            <div class="teacher-hero-panel">
                <div class="teacher-class-card">
                    <div>
                        <p class="teacher-class-label">{{ __('Assigned Class') }}</p>
                        <h3>{{ $teacherClass?->name ?? __('No class assigned') }}</h3>
                        <p class="teacher-class-meta">
                            {{ $teacherClass?->description ?: __('Assign a class to unlock student lists and class-specific content.') }}
                        </p>
                        <p class="teacher-class-meta">
                            {{ __('Section:') }} {{ $teacherClass?->section?->name ?? __('Not set') }}
                        </p>
                    </div>
                    <div class="teacher-class-stats">
                        <div>
                            <span>{{ __('Students') }}</span>
                            <strong>{{ $stats['students'] }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Topics') }}</span>
                            <strong>{{ $stats['topics'] }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Assignments') }}</span>
                            <strong>{{ $stats['assignments'] }}</strong>
                        </div>
                    </div>
                    <a class="btn ghost btn-small" href="{{ route('teacher.students.index') }}">{{ __('View Students') }}</a>
                </div>
            </div>
        </section>

        <section class="teacher-metrics">
            <a class="metric-card" href="{{ route('teacher.students.index') }}">
                <p>{{ __('Students') }}</p>
                <h2>{{ $stats['students'] }}</h2>
                <span>{{ __('In your class') }}</span>
            </a>
            <a class="metric-card" href="{{ route('teacher.classes.index') }}">
                <p>{{ __('Classes') }}</p>
                <h2>{{ $stats['classes'] }}</h2>
                <span>{{ __('Assigned') }}</span>
            </a>
            <a class="metric-card" href="{{ route('teacher.subjects.index') }}">
                <p>{{ __('Subjects') }}</p>
                <h2>{{ $stats['subjects'] }}</h2>
                <span>{{ __('Total available') }}</span>
            </a>
            <a class="metric-card" href="{{ route('teacher.topics.index') }}">
                <p>{{ __('Topics') }}</p>
                <h2>{{ $stats['topics'] }}</h2>
                <span>{{ __('For your class') }}</span>
            </a>
            <a class="metric-card" href="{{ route('teacher.assignments.index') }}">
                <p>{{ __('Assignments') }}</p>
                <h2>{{ $stats['assignments'] }}</h2>
                <span>{{ __('Active') }}</span>
            </a>
            <a class="metric-card" href="{{ route('teacher.quizzes.index') }}">
                <p>{{ __('Quizzes') }}</p>
                <h2>{{ $stats['quizzes'] }}</h2>
                <span>{{ __('Ready') }}</span>
            </a>
            <a class="metric-card" href="{{ route('teacher.exams.index') }}">
                <p>{{ __('Exams') }}</p>
                <h2>{{ $stats['exams'] }}</h2>
                <span>{{ __('Scheduled') }}</span>
            </a>
        </section>

        <section class="teacher-lanes">
            <div class="panel teacher-panel">
                <div class="panel-header">
                    <h4>{{ __('Recent Assignments') }}</h4>
                    <a class="btn ghost btn-small" href="{{ route('teacher.assignments.index') }}">{{ __('View all') }}</a>
                </div>
                <div class="panel-body">
                    @forelse ($recentAssignments as $assignment)
                        <div class="teacher-item">
                            <div>
                                <p>{{ $assignment->title }}</p>
                                <span>{{ $assignment->lesson?->topic?->title ?? 'No topic' }}</span>
                            </div>
                            <div class="teacher-item-meta">
                                <span>Due</span>
                                <strong>{{ $assignment->due_at?->format('M d') ?? '-' }}</strong>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('No assignments yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="panel teacher-panel">
                <div class="panel-header">
                    <h4>{{ __('Recent Quizzes & Exams') }}</h4>
                    <a class="btn ghost btn-small" href="{{ route('teacher.quizzes.index') }}">{{ __('View all') }}</a>
                </div>
                <div class="panel-body">
                    @forelse ($recentAssessments as $assessment)
                        <div class="teacher-item">
                            <div>
                                <p>{{ $assessment->title }}</p>
                                <span>{{ ucfirst($assessment->type) }} • {{ $assessment->subject?->name ?? __('No subject') }}</span>
                            </div>
                            <div class="teacher-item-meta">
                                <span>{{ __('Time') }}</span>
                                <strong>{{ $assessment->time_limit_minutes ? $assessment->time_limit_minutes . 'm' : '-' }}</strong>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">{{ __('No assessments yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="panel teacher-panel teacher-panel-wide">
                <div class="panel-header">
                    <h4>{{ __('Quick Access') }}</h4>
                    <span class="badge blue">{{ __('Manage') }}</span>
                </div>
                <div class="panel-body teacher-quick-grid">
                    <a class="teacher-quick-card" href="{{ route('teacher.students.index') }}">
                        <div>
                            <p>{{ __('Students') }}</p>
                            <span>{{ __('Manage class roster') }}</span>
                        </div>
                        <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                    </a>
                    <a class="teacher-quick-card" href="{{ route('teacher.topics.index') }}">
                        <div>
                            <p>{{ __('Topics') }}</p>
                            <span>{{ __('Organize learning units') }}</span>
                        </div>
                        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    </a>
                    <a class="teacher-quick-card" href="{{ route('teacher.assignments.index') }}">
                        <div>
                            <p>{{ __('Assignments') }}</p>
                            <span>{{ __('Track submissions') }}</span>
                        </div>
                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    </a>
                    <a class="teacher-quick-card" href="{{ route('teacher.quizzes.index') }}">
                        <div>
                            <p>{{ __('Quizzes') }}</p>
                            <span>{{ __('Build quick checks') }}</span>
                        </div>
                        <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                    </a>
                    <a class="teacher-quick-card" href="{{ route('teacher.exams.index') }}">
                        <div>
                            <p>{{ __('Exams') }}</p>
                            <span>{{ __('Formal evaluations') }}</span>
                        </div>
                        <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                    </a>
                    <a class="teacher-quick-card" href="{{ route('teacher.subjects.index') }}">
                        <div>
                            <p>{{ __('Subjects') }}</p>
                            <span>{{ __('Curriculum catalog') }}</span>
                        </div>
                        <i class="fa-solid fa-book" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>

        {{-- ============================================================
             USB CONTENT IMPORT — teacher view (compact "mini" variant).
             Lecturers can copy USB content + see the imported library.
             Placed below existing lanes so nothing visual is disturbed.
             ============================================================ --}}
        @include('partials.usb-import-panel', [
            'variant' => 'teacher',
            'showLibrary' => true,
            'title' => __('Flash Drive Content'),
        ])
    </main>
@endsection
