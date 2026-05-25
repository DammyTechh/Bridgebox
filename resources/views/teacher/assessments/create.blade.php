@extends('teacher.layout')

@section('title', 'Create ' . ucfirst($type))

@section('main')
    @php($routePrefix = $type === 'exam' ? 'teacher.exams' : 'teacher.quizzes')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>Create {{ ucfirst($type) }}</h1>
                <p class="subtext">Add a {{ $type }} tied to class, subject, and topic.</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route($routePrefix . '.index') }}">{{ __('Back to') }} {{ ucfirst($type) }}s</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ ucfirst($type) }} Details</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route($routePrefix . '.store') }}" method="post">
                    @csrf
                    <div class="form-field">
                        <label for="title">{{ __('Title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" required>
                        @error('title')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="school_class_id">{{ __('Class') }}</label>
                        <select id="school_class_id" name="school_class_id" required>
                            <option value="" disabled @selected(!old('school_class_id', $classes->first()?->id))>{{ __('Select a class') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('school_class_id', $classes->first()?->id) == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="subject_id">{{ __('Subject') }}</label>
                        <select id="subject_id" name="subject_id" required data-subjects-url="{{ route('teacher.subjects.by-class') }}" data-selected-subject="{{ old('subject_id') }}">
                            <option value="" disabled @selected(!old('subject_id'))>{{ __('Select a class first') }}</option>
                        </select>
                        @error('subject_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="topic_id">{{ __('Topic') }}</label>
                        <select id="topic_id" name="topic_id" required data-topics-url="{{ route('teacher.topics.by-subject') }}" data-selected-topic="{{ old('topic_id') }}">
                            <option value="" disabled @selected(!old('topic_id'))>{{ __('Select a subject first') }}</option>
                        </select>
                        @error('topic_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="time_limit_minutes">{{ __('Duration (minutes)') }}</label>
                        <input id="time_limit_minutes" name="time_limit_minutes" type="number" min="1" max="600" value="{{ old('time_limit_minutes') }}" required>
                        @error('time_limit_minutes')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="total_mark">{{ __('Total Mark') }}</label>
                        <input id="total_mark" name="total_mark" type="number" min="1" max="1000" value="{{ old('total_mark') }}" required>
                        @error('total_mark')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="pass_mark">{{ __('Pass Mark') }}</label>
                        <input id="pass_mark" name="pass_mark" type="number" min="0" max="1000" value="{{ old('pass_mark') }}" required>
                        @error('pass_mark')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="retake_attempts">{{ __('Retake attempts') }}</label>
                        <input id="retake_attempts" name="retake_attempts" type="number" min="0" max="100" value="{{ old('retake_attempts', 0) }}" required>
                        @error('retake_attempts')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label for="description">{{ ucfirst($type) }} {{ __('summary') }}</label>
                        <textarea id="description" name="description" required data-wysiwyg>{{ old('description') }}</textarea>
                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions" style="margin-top: 10px;">
                        <button class="btn primary" type="submit">{{ __('Create') }} {{ ucfirst($type) }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const subjectSelect = document.getElementById('subject_id');
        const classSelect = document.getElementById('school_class_id');
        const topicSelect = document.getElementById('topic_id');
        if (subjectSelect && topicSelect && classSelect) {
            const topicsUrl = topicSelect.dataset.topicsUrl;
            const loadSubjects = async (selectedSubjectId) => {
                const classId = classSelect.value;
                if (!classId) {
                    subjectSelect.innerHTML = '<option value="" disabled selected>Select a class first</option>';
                    topicSelect.innerHTML = '<option value="" disabled selected>Select a subject first</option>';
                    return;
                }

                subjectSelect.innerHTML = '<option value="" disabled selected>Loading subjects...</option>';
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
                    let options = '<option value="" disabled>Select a subject</option>';
                    data.forEach((subject) => {
                        const selected = selectedSubjectId && String(subject.id) === String(selectedSubjectId) ? 'selected' : '';
                        options += `<option value="${subject.id}" ${selected}>${subject.name}</option>`;
                    });
                    subjectSelect.innerHTML = options;
                    await loadTopics(topicSelect.dataset.selectedTopic || null);
                } catch (error) {
                    subjectSelect.innerHTML = '<option value="" disabled selected>Unable to load subjects</option>';
                    topicSelect.innerHTML = '<option value="" disabled selected>Select a subject first</option>';
                }
            };

            const loadTopics = async (selectedTopicId) => {
                const subjectId = subjectSelect.value;
                const classId = classSelect ? classSelect.value : '';
                if (!subjectId) {
                    topicSelect.innerHTML = '<option value="" disabled selected>Select a subject first</option>';
                    return;
                }

                topicSelect.innerHTML = '<option value="" disabled selected>Loading topics...</option>';
                try {
                    const url = new URL(topicsUrl, window.location.origin);
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
                    let options = '<option value="" disabled>Select a topic</option>';
                    data.forEach((topic) => {
                        const selected = selectedTopicId && String(topic.id) === String(selectedTopicId) ? 'selected' : '';
                        options += `<option value="${topic.id}" ${selected}>${topic.title}</option>`;
                    });
                    topicSelect.innerHTML = options;
                } catch (error) {
                    topicSelect.innerHTML = '<option value="" disabled selected>Unable to load topics</option>';
                }
            };

            classSelect.addEventListener('change', () => loadSubjects(null));
            subjectSelect.addEventListener('change', () => loadTopics(null));

            const initialSubject = subjectSelect.dataset.selectedSubject || null;
            loadSubjects(initialSubject);
        }
    </script>
@endpush
