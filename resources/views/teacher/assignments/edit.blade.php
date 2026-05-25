@extends('teacher.layout')

@section('title', 'Edit Assignment')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Edit Assignment') }}</h1>
                <p class="subtext">{{ __('Update assignment details.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.assignments.index') }}">{{ __('Back to Assignments') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Assignment Details') }}</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                @php
                    $selectedClassId = old('school_class_id', $assignment->lesson?->topic?->school_class_id);
                    $selectedSubjectId = old('subject_id', $assignment->lesson?->topic?->subject_id);
                    $selectedTopicId = old('topic_id', $assignment->lesson?->topic_id);
                    $selectedLessonId = old('lesson_id', $assignment->lesson_id);
                @endphp
                <form class="form-grid" action="{{ route('teacher.assignments.update', $assignment) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-field">
                        <label for="title">{{ __('Title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $assignment->title) }}" required>
                        @error('title')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="school_class_id">{{ __('Class') }}</label>
                        <select id="school_class_id" name="school_class_id" required>
                            <option value="" disabled @selected(!$selectedClassId)>{{ __('Select a class') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="subject_id">{{ __('Subject') }}</label>
                        <select id="subject_id" name="subject_id" required data-subjects-url="{{ route('teacher.subjects.by-class') }}" data-selected-subject="{{ $selectedSubjectId }}">
                            <option value="" disabled @selected(!$selectedSubjectId)>{{ __('Select a class first') }}</option>
                        </select>
                        @error('subject_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="topic_id">{{ __('Topic') }}</label>
                        <select id="topic_id" name="topic_id" required data-topics-url="{{ route('teacher.topics.by-subject') }}" data-selected-topic="{{ $selectedTopicId }}">
                            <option value="" disabled @selected(!$selectedTopicId)>{{ __('Select a subject first') }}</option>
                        </select>
                        @error('topic_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="lesson_id">{{ __('Lesson') }}</label>
                        <select id="lesson_id" name="lesson_id" required data-lessons-url="{{ route('teacher.topics.lessons.by-topic') }}" data-selected-lesson="{{ $selectedLessonId }}">
                            <option value="" disabled @selected(!$selectedLessonId)>{{ __('Select a topic first') }}</option>
                            @foreach ($lessons as $lesson)
                                <option value="{{ $lesson->id }}" @selected($selectedLessonId == $lesson->id)>{{ $lesson->title }}</option>
                            @endforeach
                        </select>
                        @error('lesson_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="due_at">{{ __('Deadline') }}</label>
                        <input id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at', $assignment->due_at?->format('Y-m-d\TH:i')) }}" required>
                        @error('due_at')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="max_points">{{ __('Total Mark') }}</label>
                        <input id="max_points" name="max_points" type="number" min="1" max="1000" value="{{ old('max_points', $assignment->max_points) }}" required>
                        @error('max_points')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="pass_mark">{{ __('Pass Mark') }}</label>
                        <input id="pass_mark" name="pass_mark" type="number" min="0" max="1000" value="{{ old('pass_mark', $assignment->pass_mark) }}" required>
                        @error('pass_mark')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="retake_attempts">{{ __('Retake attempts') }}</label>
                        <input id="retake_attempts" name="retake_attempts" type="number" min="0" max="100" value="{{ old('retake_attempts', $assignment->retake_attempts) }}" required>
                        @error('retake_attempts')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label class="checkbox">
                            <input type="checkbox" name="allow_late" value="1" {{ old('allow_late', $assignment->allow_late) ? 'checked' : '' }}>
                            <span>{{ __('Allow late submission') }}</span>
                        </label>
                    </div>

                    <div class="form-field" data-late-fields>
                        <label for="late_mark">{{ __('Late submission mark') }}</label>
                        <input id="late_mark" name="late_mark" type="number" min="0" max="1000" value="{{ old('late_mark', $assignment->late_mark) }}">
                        @error('late_mark')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field" data-late-fields>
                        <label for="late_due_at">{{ __('Late submission deadline') }}</label>
                        <input id="late_due_at" name="late_due_at" type="datetime-local" value="{{ old('late_due_at', $assignment->late_due_at?->format('Y-m-d\TH:i')) }}">
                        @error('late_due_at')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label for="description">{{ __('Assignment description') }}</label>
                        <textarea id="description" name="description" required data-wysiwyg>{{ old('description', $assignment->description) }}</textarea>
                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions" style="margin-top: 40px;">
                        <button class="btn primary" type="submit">{{ __('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const classSelect = document.getElementById('school_class_id');
        const subjectSelect = document.getElementById('subject_id');
        const topicSelect = document.getElementById('topic_id');
        const lessonSelect = document.getElementById('lesson_id');

        const loadSubjects = async (selectedSubjectId) => {
            if (!classSelect || !subjectSelect) {
                return null;
            }

            const classId = classSelect.value;
            if (!classId) {
                subjectSelect.innerHTML = '<option value="" disabled selected>Select a class first</option>';
                topicSelect.innerHTML = '<option value="" disabled selected>Select a subject first</option>';
                lessonSelect.innerHTML = '<option value="" disabled selected>Select a topic first</option>';
                return null;
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
                return selectedSubjectId || subjectSelect.value || null;
            } catch (error) {
                subjectSelect.innerHTML = '<option value="" disabled selected>Unable to load subjects</option>';
                topicSelect.innerHTML = '<option value="" disabled selected>Select a subject first</option>';
                lessonSelect.innerHTML = '<option value="" disabled selected>Select a topic first</option>';
                return null;
            }
        };

        const loadTopics = async (selectedTopicId) => {
            if (!subjectSelect || !topicSelect) {
                return null;
            }

            const subjectId = subjectSelect.value;
            const classId = classSelect ? classSelect.value : '';
            if (!subjectId) {
                topicSelect.innerHTML = '<option value="" disabled selected>Select a subject first</option>';
                return null;
            }

            topicSelect.innerHTML = '<option value="" disabled selected>Loading topics...</option>';
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
                let options = '<option value="" disabled>Select a topic</option>';
                data.forEach((topic) => {
                    const selected = selectedTopicId && String(topic.id) === String(selectedTopicId) ? 'selected' : '';
                    options += `<option value="${topic.id}" ${selected}>${topic.title}</option>`;
                });
                topicSelect.innerHTML = options;
                return selectedTopicId || topicSelect.value || null;
            } catch (error) {
                topicSelect.innerHTML = '<option value="" disabled selected>Unable to load topics</option>';
                return null;
            }
        };

        const loadLessons = async (topicId, selectedLessonId) => {
            if (!lessonSelect) {
                return;
            }

            if (!topicId) {
                lessonSelect.innerHTML = '<option value="" disabled selected>Select a topic first</option>';
                return;
            }

            lessonSelect.innerHTML = '<option value="" disabled selected>Loading lessons...</option>';
            try {
                const url = new URL(lessonSelect.dataset.lessonsUrl, window.location.origin);
                url.searchParams.set('topic_id', topicId);
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = response.ok ? await response.json() : [];
                let options = '<option value="" disabled>Select a lesson</option>';
                data.forEach((lesson) => {
                    const selected = selectedLessonId && String(lesson.id) === String(selectedLessonId) ? 'selected' : '';
                    options += `<option value="${lesson.id}" ${selected}>${lesson.title}</option>`;
                });
                lessonSelect.innerHTML = options;
            } catch (error) {
                lessonSelect.innerHTML = '<option value="" disabled selected>Unable to load lessons</option>';
            }
        };

        const allowLate = document.querySelector('input[name="allow_late"]');
        const lateFields = document.querySelectorAll('[data-late-fields]');
        const lateMark = document.getElementById('late_mark');
        const lateDue = document.getElementById('late_due_at');
        const toggleLateFields = () => {
            const enabled = allowLate && allowLate.checked;
            lateFields.forEach((field) => {
                field.style.display = enabled ? '' : 'none';
            });
            if (lateMark) {
                lateMark.required = enabled;
            }
            if (lateDue) {
                lateDue.required = enabled;
            }
        };
        if (allowLate) {
            allowLate.addEventListener('change', toggleLateFields);
            toggleLateFields();
        }

        if (subjectSelect && topicSelect && lessonSelect) {
            if (classSelect) {
                classSelect.addEventListener('change', async () => {
                    const selectedSubjectId = await loadSubjects(null);
                    const selectedTopicId = await loadTopics(null);
                    await loadLessons(selectedTopicId, null);
                });
            }

            subjectSelect.addEventListener('change', async () => {
                const selectedTopicId = await loadTopics(null);
                await loadLessons(selectedTopicId, null);
            });

            topicSelect.addEventListener('change', () => {
                loadLessons(topicSelect.value, null);
            });

            const initialSubjectId = subjectSelect.dataset.selectedSubject || null;
            const initialTopicId = topicSelect.dataset.selectedTopic || null;
            const initialLessonId = lessonSelect.dataset.selectedLesson || null;
            loadSubjects(initialSubjectId).then((resolvedSubjectId) => {
                loadTopics(initialTopicId).then((resolvedTopicId) => {
                    loadLessons(resolvedTopicId, initialLessonId);
                });
            });
        }
    </script>
@endpush

