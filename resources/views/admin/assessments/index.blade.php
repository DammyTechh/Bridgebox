@extends('admin.layout')

@section('title', ucfirst($type) . 's')

@section('main')
    @php($routePrefix = $type === 'exam' ? 'admin.exams' : 'admin.quizzes')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin') }}</p>
                <h1>{{ ucfirst($type) }}s</h1>
                <p class="subtext">Create and manage {{ $type }}s by {{ $installMode->isGeneric() ? 'course and topic' : 'class, subject, and topic' }}.</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route($routePrefix . '.create') }}">Add {{ ucfirst($type) }}</a>
                @php($exportQuery = request()->query())
                <a class="btn ghost" href="{{ route($routePrefix . '.export', array_merge($exportQuery, ['format' => 'csv'])) }}">{{ __('Export CSV') }}</a>
                <a class="btn ghost" href="{{ route($routePrefix . '.export', array_merge($exportQuery, ['format' => 'xlsx'])) }}">{{ __('Export Excel') }}</a>
                <a class="btn ghost" href="{{ route($routePrefix . '.export', array_merge($exportQuery, ['format' => 'pdf'])) }}">{{ __('Export PDF') }}</a>
                <a class="btn ghost" href="{{ route($routePrefix . '.attempts.index') }}">{{ __('Results') }}</a>
                <a class="btn ghost" href="{{ route('dashboard.admin') }}">{{ __('Back to Dashboard') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if (session('message'))
            <div class="alert alert-dismissible {{ session('status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="4000">
                <span data-alert-message>{{ session('message') }}</span>
                <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="Dismiss alert">&times;</button>
            </div>
        @endif

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ ucfirst($type) }} List</h4>
                <span class="badge blue">{{ $assessments->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedClassId || $selectedSubjectId || $selectedTopicId)
                    <form class="search-form" method="get" action="{{ route($routePrefix . '.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search by title') }}" value="{{ $search }}">
                        @if ($installMode->isSchool())
                        <select class="search-input" name="class_id" id="class_id">
                            <option value="" @selected(!$selectedClassId)>{{ __('All classes') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @endif
                        <select class="search-input" name="subject_id" id="subject_id" data-subjects-url="{{ route('admin.subjects.by-class') }}" data-selected-subject="{{ $selectedSubjectId }}">
                            <option value="" @selected(!$selectedSubjectId)>{{ $installMode->isGeneric() ? __('All courses') : __('All subjects') }}</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected($selectedSubjectId == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <select class="search-input" name="topic_id" id="topic_id" data-topics-url="{{ route('admin.topics.by-subject') }}" data-selected-topic="{{ $selectedTopicId }}">
                            <option value="" @selected(!$selectedTopicId)>{{ __('All topics') }}</option>
                        </select>
                        <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route($routePrefix . '.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $assessments->count() }} of {{ $assessments->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Title') }}</th>
                                @if ($installMode->isSchool())
                                <th>{{ __('Class') }}</th>
                                @endif
                                <th>{{ $installMode->isGeneric() ? __('Course') : __('Subject') }}</th>
                                <th>{{ __('Topic') }}</th>
                                <th>{{ __('Time') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assessments as $index => $assessment)
                                <tr>
                                    <td>{{ $assessments->firstItem() + $index }}</td>
                                    <td>{{ $assessment->title }}</td>
                                    @if ($installMode->isSchool())
                                    <td>{{ $assessment->schoolClass?->name ?? '-' }}</td>
                                    @endif
                                    <td>{{ $assessment->subject?->name ?? '-' }}</td>
                                    <td>{{ $assessment->topic?->title ?? '-' }}</td>
                                    <td>{{ $assessment->time_limit_minutes ? $assessment->time_limit_minutes . ' min' : '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route($routePrefix . '.attempts.index', ['assessment_id' => $assessment->id]) }}">{{ __('Results') }}</a>
                                            <a class="btn ghost btn-small" href="{{ route($routePrefix . '.questions.index', $assessment) }}">{{ __('Questions') }}</a>
                                            <a class="btn ghost btn-small" href="{{ route($routePrefix . '.edit', $assessment) }}">{{ __('Edit') }}</a>
                                            <form method="post" action="{{ route($routePrefix . '.delete', $assessment) }}" data-confirm="Delete this {{ $type }}?" style="display:inline-block;">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="{{ $installMode->isSchool() ? 7 : 6 }}">No {{ $type }}s found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $assessments])
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const subjectSelect = document.getElementById('subject_id');
        const topicSelect = document.getElementById('topic_id');
        @if ($installMode->isSchool())
        const classSelect = document.getElementById('class_id');
        @endif
        if (subjectSelect && topicSelect) {
            const loadTopics = async (selectedTopicId) => {
                const subjectId = subjectSelect.value;
                @if ($installMode->isSchool())
                const classId = classSelect ? classSelect.value : '';
                @endif
                if (!subjectId) {
                    topicSelect.innerHTML = '<option value="">All topics</option>';
                    return;
                }
                topicSelect.innerHTML = '<option value="">Loading topics...</option>';
                try {
                    const url = new URL(topicSelect.dataset.topicsUrl, window.location.origin);
                    url.searchParams.set('subject_id', subjectId);
                    @if ($installMode->isSchool())
                    if (classId) {
                        url.searchParams.set('class_id', classId);
                    }
                    @endif
                    const response = await fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const data = response.ok ? await response.json() : [];
                    let options = '<option value="">All topics</option>';
                    data.forEach((topic) => {
                        const selected = selectedTopicId && String(topic.id) === String(selectedTopicId) ? 'selected' : '';
                        options += `<option value="${topic.id}" ${selected}>${topic.title}</option>`;
                    });
                    topicSelect.innerHTML = options;
                } catch (error) {
                    topicSelect.innerHTML = '<option value="">All topics</option>';
                }
            };

            subjectSelect.addEventListener('change', () => loadTopics(null));
            @if ($installMode->isSchool())
            const defaultSubjectOptions = subjectSelect.innerHTML;
            const loadSubjects = async (selectedSubjectId) => {
                if (!classSelect) return;
                const classId = classSelect.value;
                if (!classId) {
                    subjectSelect.innerHTML = defaultSubjectOptions;
                    if (selectedSubjectId) subjectSelect.value = selectedSubjectId;
                    await loadTopics(topicSelect.dataset.selectedTopic || null);
                    return;
                }
                subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
                try {
                    const url = new URL(subjectSelect.dataset.subjectsUrl, window.location.origin);
                    url.searchParams.set('class_id', classId);
                    const response = await fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const data = response.ok ? await response.json() : [];
                    let options = '<option value="">All subjects</option>';
                    data.forEach((subject) => {
                        const selected = selectedSubjectId && String(subject.id) === String(selectedSubjectId) ? 'selected' : '';
                        options += `<option value="${subject.id}" ${selected}>${subject.name}</option>`;
                    });
                    subjectSelect.innerHTML = options;
                    await loadTopics(topicSelect.dataset.selectedTopic || null);
                } catch (error) {
                    subjectSelect.innerHTML = defaultSubjectOptions;
                    await loadTopics(topicSelect.dataset.selectedTopic || null);
                }
            };
            classSelect.addEventListener('change', () => loadSubjects(null));
            const initialSubject = subjectSelect.dataset.selectedSubject || null;
            loadSubjects(initialSubject);
            @else
            const initialTopic = topicSelect.dataset.selectedTopic || null;
            if (subjectSelect.value) loadTopics(initialTopic);
            @endif
        }
    </script>
@endpush
