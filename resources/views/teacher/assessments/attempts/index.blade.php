@extends('teacher.layout')

@section('title', ucfirst($type) . ' Results')

@section('main')
    @php($routePrefix = $type === 'exam' ? 'teacher.exams' : 'teacher.quizzes')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ ucfirst($type) }} Results</h1>
                <p class="subtext">Review student attempts for {{ $type }}s.</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route($routePrefix . '.index') }}">{{ __('Back to') }} {{ ucfirst($type) }}s</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if (!($teacherClass?->id))
            <div class="alert alert-error" role="status">
                <span data-alert-message>{{ __('Your account has no class assigned. Results will not appear until a class is assigned.') }}</span>
            </div>
        @endif

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ __('Attempts') }}</h4>
                <span class="badge blue">{{ $attempts->total() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-toolbar">
                    @php($hasFilters = $search || $selectedClassId || $selectedSubjectId || $selectedTopicId || $selectedAssessmentId || $selectedStudentId)
                    <form class="search-form" method="get" action="{{ route($routePrefix . '.attempts.index') }}">
                        <input class="search-input" type="text" name="q" placeholder="{{ __('Search student or assessment') }}" value="{{ $search }}">
                        <select class="search-input" name="class_id" id="class_id">
                            <option value="" @selected(!$selectedClassId)>{{ __('All classes') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <select class="search-input" name="subject_id" id="subject_id" data-subjects-url="{{ route('teacher.subjects.by-class') }}" data-selected-subject="{{ $selectedSubjectId }}">
                            <option value="" @selected(!$selectedSubjectId)>{{ __('All subjects') }}</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected($selectedSubjectId == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <select class="search-input" name="topic_id" id="topic_id" data-topics-url="{{ route('teacher.topics.by-subject') }}" data-selected-topic="{{ $selectedTopicId }}">
                            <option value="" @selected(!$selectedTopicId)>{{ __('All topics') }}</option>
                            @foreach ($topics as $topic)
                                <option value="{{ $topic->id }}" @selected($selectedTopicId == $topic->id)>{{ $topic->title }}</option>
                            @endforeach
                        </select>
                        <select class="search-input" name="assessment_id">
                            <option value="" @selected(!$selectedAssessmentId)>{{ __('All') }} {{ $type }}s</option>
                            @foreach ($assessments as $assessment)
                                <option value="{{ $assessment->id }}" @selected($selectedAssessmentId == $assessment->id)>{{ $assessment->title }}</option>
                            @endforeach
                        </select>
                        <select class="search-input" name="student_id">
                            <option value="" @selected(!$selectedStudentId)>{{ __('All students') }}</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected($selectedStudentId == $student->id)>{{ $student->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn ghost btn-small" type="submit">{{ __('Filter') }}</button>
                        @if ($hasFilters)
                            <a class="btn ghost btn-small" href="{{ route($routePrefix . '.attempts.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </form>
                    <span class="text-muted">Showing {{ $attempts->count() }} of {{ $attempts->total() }}</span>
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('#') }}</th>
                                <th>{{ __('Student') }}</th>
                                <th>{{ ucfirst($type) }}</th>
                                <th>{{ __('Class') }}</th>
                                <th>{{ __('Score') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Completed') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attempts as $index => $attempt)
                                @php($score = $attempt->score ?? 0)
                                @php($total = $attempt->total ?? 0)
                                @php($passMark = $attempt->assessment?->pass_mark ?? 0)
                                <tr>
                                    <td>{{ $attempts->firstItem() + $index }}</td>
                                    <td>{{ $attempt->user?->name ?? '-' }}</td>
                                    <td>{{ $attempt->assessment?->title ?? '-' }}</td>
                                    <td>{{ $attempt->assessment?->schoolClass?->name ?? '-' }}</td>
                                    <td>{{ $score }} / {{ $total }}</td>
                                    <td>
                                        <span class="badge {{ $attempt->status === 'completed' ? ($score >= $passMark ? 'green' : 'rose') : 'blue' }}">
                                            {{ $attempt->status === 'completed' ? ($score >= $passMark ? __('Passed') : __('Needs Review')) : __('In Progress') }}
                                        </span>
                                    </td>
                                    <td>{{ $attempt->completed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route($routePrefix . '.attempts.show', $attempt) }}">{{ __('Review') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="8">{{ __('No attempts found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('admin.users.partials.pagination', ['paginator' => $attempts])
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const subjectSelect = document.getElementById('subject_id');
        const classSelect = document.getElementById('class_id');
        const topicSelect = document.getElementById('topic_id');
        if (subjectSelect && topicSelect) {
            const defaultSubjectOptions = subjectSelect.innerHTML;
            const loadTopics = async (selectedTopicId) => {
                const subjectId = subjectSelect.value;
                const classId = classSelect ? classSelect.value : '';
                if (!subjectId) {
                    topicSelect.innerHTML = '<option value="">All topics</option>';
                    return;
                }

                topicSelect.innerHTML = '<option value="">Loading topics...</option>';
                try {
                    const url = new URL(topicSelect.dataset.topicsUrl, window.location.origin);
                    url.searchParams.set('subject_id', subjectId);
                    if (classId) {
                        url.searchParams.set('class_id', classId);
                    }
                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
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

            const loadSubjects = async (selectedSubjectId) => {
                if (!classSelect) {
                    return;
                }

                const classId = classSelect.value;
                if (!classId) {
                    subjectSelect.innerHTML = defaultSubjectOptions;
                    if (selectedSubjectId) {
                        subjectSelect.value = selectedSubjectId;
                    }
                    await loadTopics(topicSelect.dataset.selectedTopic || null);
                    return;
                }

                subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
                try {
                    const url = new URL(subjectSelect.dataset.subjectsUrl, window.location.origin);
                    url.searchParams.set('class_id', classId);
                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
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

            subjectSelect.addEventListener('change', () => loadTopics(null));
            if (classSelect) {
                classSelect.addEventListener('change', () => loadSubjects(null));
            }

            const initialSubject = subjectSelect.dataset.selectedSubject || null;
            loadSubjects(initialSubject);
        }
    </script>
@endpush
