@extends('student.layout')

@section('title', 'Lessons')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Student') }}</p>
                <h1>{{ __('Lessons') }}</h1>
                <p class="subtext">{{ __('Read or download lessons for your class.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('dashboard.student') }}">{{ __('Back to Dashboard') }}</a>
            </div>
        </header>

        @if (!($student?->school_class_id))
            <div class="alert alert-error" role="status">
                <span data-alert-message>{{ __('Your account has no class assigned. Lessons will not appear until a class is assigned.') }}</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Lessons') }}</h4>
                <span class="badge blue">{{ $lessons->total() }}</span>
            </div>

            {{-- Filter toolbar --}}
            <div class="table-toolbar" style="margin-bottom:20px;">
                @php($hasFilters = $search || $selectedSubjectId || $selectedTopicId)
                <form class="search-form" method="get" action="{{ route('student.lessons.index') }}">
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
                        <a class="btn ghost btn-small" href="{{ route('student.lessons.index') }}">{{ __('Clear') }}</a>
                    @endif
                </form>
                <span class="text-muted">Showing {{ $lessons->count() }} of {{ $lessons->total() }}</span>
            </div>

            <div class="item-card-grid">
                @forelse ($lessons as $lesson)
                    <a class="item-card" href="{{ route('student.lessons.show', $lesson) }}">
                        <div class="item-card-head">
                            <div class="item-card-icon ic-blue">
                                <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                            </div>
                            <div class="item-card-tags">
                                @if ($lesson->topic?->subject)
                                    <span class="item-tag">{{ $lesson->topic->subject->name }}</span>
                                @endif
                                @if ($lesson->topic)
                                    <span class="item-tag">{{ $lesson->topic->title }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item-card-body">
                            <h3 class="item-card-title">{{ $lesson->title }}</h3>
                            @if ($lesson->content)
                                <p class="item-card-desc">{{ Str::limit(html_entity_decode(strip_tags($lesson->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 90) }}</p>
                            @endif
                        </div>
                        <div class="item-card-footer">
                            @if ($lesson->file_name)
                                <span class="item-chip">
                                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i>
                                    {{ strtoupper($lesson->file_type ?? 'File') }}
                                </span>
                            @else
                                <span></span>
                            @endif
                            @if (in_array($lesson->id, $completedLessonIds))
                                <span class="item-chip item-chip--done">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    {{ __('Done') }}
                                </span>
                            @else
                                <span class="item-action">{{ __('Open Lesson') }} &rarr;</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="card-empty">
                        <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                        <p>{{ __('No lessons found.') }}</p>
                    </div>
                @endforelse
            </div>

            @include('admin.users.partials.pagination', ['paginator' => $lessons])
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const subjectSelect = document.getElementById('subject_id');
        const topicSelect   = document.getElementById('topic_id');
        if (subjectSelect && topicSelect) {
            const loadTopics = async (selectedTopicId) => {
                const subjectId = subjectSelect.value;
                if (!subjectId) { topicSelect.innerHTML = '<option value="">All topics</option>'; return; }
                topicSelect.innerHTML = '<option value="">Loading…</option>';
                try {
                    const url = new URL(topicSelect.dataset.topicsUrl, window.location.origin);
                    url.searchParams.set('subject_id', subjectId);
                    const res  = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
                    const data = res.ok ? await res.json() : [];
                    let opts = '<option value="">All topics</option>';
                    data.forEach(t => { opts += `<option value="${t.id}"${selectedTopicId && String(t.id) === String(selectedTopicId) ? ' selected' : ''}>${t.title}</option>`; });
                    topicSelect.innerHTML = opts;
                } catch { topicSelect.innerHTML = '<option value="">All topics</option>'; }
            };
            subjectSelect.addEventListener('change', () => loadTopics(null));
            loadTopics(topicSelect.dataset.selectedTopic || null);
        }
    </script>
@endpush