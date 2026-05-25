@extends('teacher.layout')

@section('title', 'Edit Topic')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Edit Topic') }}</h1>
                <p class="subtext">{{ __('Update topic details.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.topics.index') }}">{{ __('Back to Topics') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Topic Details') }}</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route('teacher.topics.update', $topic) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-field">
                        <label for="title">{{ __('Title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $topic->title) }}" required>
                        @error('title')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="school_class_id">{{ __('Class') }}</label>
                        <select id="school_class_id" name="school_class_id" required>
                            <option value="" disabled>{{ __('Select a class') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('school_class_id', $topic->school_class_id) == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="subject_id">{{ __('Subject') }}</label>
                        <select id="subject_id" name="subject_id" required data-subjects-url="{{ route('teacher.subjects.by-class') }}" data-selected-subject="{{ old('subject_id', $topic->subject_id) }}">
                            <option value="" disabled>{{ __('Select a class first') }}</option>
                        </select>
                        @error('subject_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label for="description">{{ __('Description (optional)') }}</label>
                        <textarea id="description" name="description">{{ old('description', $topic->description) }}</textarea>
                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
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
        if (classSelect && subjectSelect) {
            const loadSubjects = async (selectedSubjectId) => {
                const classId = classSelect.value;
                if (!classId) {
                    subjectSelect.innerHTML = '<option value="" disabled selected>Select a class first</option>';
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
                } catch (error) {
                    subjectSelect.innerHTML = '<option value="" disabled selected>Unable to load subjects</option>';
                }
            };

            classSelect.addEventListener('change', () => loadSubjects(null));

            const initialSelected = subjectSelect.dataset.selectedSubject || null;
            loadSubjects(initialSelected);
        }
    </script>
@endpush
