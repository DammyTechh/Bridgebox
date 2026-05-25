@extends('student.layout')

@section('title', 'Assignments')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ __('Assignments') }}</h1>
                <p class="subtext">{{ __('Submit assignments for your class lessons.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('dashboard.student') }}">{{ __('Back to Dashboard') }}</a>
            </div>
        </header>

        @if (session('message'))
            <div class="alert alert-dismissible {{ session('status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="4000">
                <span data-alert-message>{{ session('message') }}</span>
                <button class="alert-close" type="button" data-alert-close aria-label="{{ __('Dismiss') }}">&times;</button>
            </div>
        @endif

        @if (!($student?->school_class_id))
            <div class="alert alert-error" role="status">
                <span data-alert-message>{{ __('Your account has no class assigned. Assignments will not appear until a class is assigned.') }}</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Assignments') }}</h4>
                <span class="badge blue">{{ $assignments->total() }}</span>
            </div>

            <div class="table-toolbar" style="margin-bottom:20px;">
                @php($hasFilters = $search || $selectedSubjectId || $selectedTopicId)
                <form class="search-form" method="get" action="{{ route('student.assignments.index') }}">
                    <input class="search-input" type="text" name="q" placeholder="{{ __('Search by title') }}" value="{{ $search }}">
                    <select class="search-input" name="subject_id" id="subject_id">
                        <option value="" @selected(!$selectedSubjectId)>{{ __('All subjects') }}</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubjectId == $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    <select class="search-input" name="topic_id" id="topic_id"
                            data-topics-url="{{ route('student.topics.by-subject') }}"
                            data-selected-topic="{{ $selectedTopicId }}">
                        <option value="" @selected(!$selectedTopicId)>{{ __('All topics') }}</option>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->id }}" @selected($selectedTopicId == $topic->id)>{{ $topic->title }}</option>
                        @endforeach
                    </select>
                    <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                    @if ($hasFilters)
                        <a class="btn ghost btn-small" href="{{ route('student.assignments.index') }}">{{ __('Clear') }}</a>
                    @endif
                </form>
                <span class="text-muted">Showing {{ $assignments->count() }} of {{ $assignments->total() }}</span>
            </div>

            <div class="item-card-grid">
                @forelse ($assignments as $assignment)
                    @php($submission = $assignment->submissions->first())
                    <a class="item-card" href="{{ route('student.assignments.show', $assignment) }}">
                        <div class="item-card-head">
                            <div class="item-card-icon ic-coral">
                                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                            </div>
                            <div class="item-card-tags">
                                @if ($assignment->lesson?->topic?->subject)
                                    <span class="item-tag">{{ $assignment->lesson->topic->subject->name }}</span>
                                @endif
                                @if ($assignment->lesson?->topic)
                                    <span class="item-tag">{{ $assignment->lesson->topic->title }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item-card-body">
                            <h3 class="item-card-title">{{ $assignment->title }}</h3>
                            @if ($assignment->lesson)
                                <p class="item-card-desc">Lesson: {{ $assignment->lesson->title }}</p>
                            @endif
                        </div>
                        <div class="item-card-footer">
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                @if ($assignment->due_at)
                                    <span class="item-chip">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        Due {{ $assignment->due_at->format('d M Y') }}
                                    </span>
                                @endif
                                <span class="badge {{ $submission ? 'green' : 'gold' }}">
                                    {{ $submission ? __('Submitted') : __('Pending') }}
                                </span>
                            </div>
                            <span class="item-action">{{ __('Open') }} &rarr;</span>
                        </div>
                    </a>
                @empty
                    <div class="card-empty">
                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                        <p>{{ __('No assignments found.') }}</p>
                    </div>
                @endforelse
            </div>

            @include('admin.users.partials.pagination', ['paginator' => $assignments])
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const subjectSelect = document.getElementById('subject_id');
        const topicSelect   = document.getElementById('topic_id');
        if (subjectSelect && topicSelect) {
            const loadTopics = async (sel) => {
                const sid = subjectSelect.value;
                if (!sid) { topicSelect.innerHTML = '<option value="">All topics</option>'; return; }
                topicSelect.innerHTML = '<option value="">Loading…</option>';
                try {
                    const url = new URL(topicSelect.dataset.topicsUrl, window.location.origin);
                    url.searchParams.set('subject_id', sid);
                    const res  = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
                    const data = res.ok ? await res.json() : [];
                    let opts = '<option value="">All topics</option>';
                    data.forEach(t => { opts += `<option value="${t.id}"${sel && String(t.id) === String(sel) ? ' selected' : ''}>${t.title}</option>`; });
                    topicSelect.innerHTML = opts;
                } catch { topicSelect.innerHTML = '<option value="">All topics</option>'; }
            };
            subjectSelect.addEventListener('change', () => loadTopics(null));
            loadTopics(topicSelect.dataset.selectedTopic || null);
        }
    </script>
@endpush