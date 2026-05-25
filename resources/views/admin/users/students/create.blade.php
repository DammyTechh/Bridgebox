@extends('admin.layout')

@section('title', 'Create Student')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin User Management') }}</p>
                <h1>{{ __('Create Student') }}</h1>
                <p class="subtext">{{ __('Add a new student account for the learning hub.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.users.students.index') }}">{{ __('Back to Students') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Student Details') }}</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route('admin.users.students.store') }}" method="post">
                    @csrf
                    <div class="form-field">
                        <label for="name">{{ __('Full name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="email">{{ __('Email address') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="phone">{{ __('Phone (optional)') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="school_class_id">{{ __('Class') }}</label>
                        <select id="school_class_id" name="school_class_id" required>
                            <option value="" disabled @selected(!old('school_class_id'))>{{ __('Select a class') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('school_class_id') == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="department">{{ __('Department (optional)') }}</label>
                        <select id="department" name="department">
                            <option value="" @selected(!old('department'))>{{ __('Select a department') }}</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->name }}" @selected(old('department') === $department->name)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="admission_id">{{ __('Admission ID (optional)') }}</label>
                        <input id="admission_id" name="admission_id" type="text" value="{{ old('admission_id') }}">
                        @error('admission_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field-full">
                        <label class="checkbox">
                            <input type="checkbox" name="auto_generate" value="1" {{ old('auto_generate') ? 'checked' : '' }}>
                            <span>{{ __('Auto-generate a password (recommended)') }}</span>
                        </label>
                    </div>

                    <div class="form-field">
                        <label for="password">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" autocomplete="new-password">
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">{{ __('Confirm password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Create Student') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        const autoToggle = document.querySelector('input[name="auto_generate"]');
        const passwordFields = document.querySelectorAll('#password, #password_confirmation');
        if (autoToggle) {
            const syncFields = () => {
                const disabled = autoToggle.checked;
                passwordFields.forEach((field) => {
                    field.disabled = disabled;
                    if (disabled) {
                        field.value = '';
                    }
                });
            };
            autoToggle.addEventListener('change', syncFields);
            syncFields();
        }
    </script>
@endpush
