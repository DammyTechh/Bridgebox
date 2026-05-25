@extends('teacher.layout')

@section('title', __('Edit Student'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Edit Student') }}</h1>
                <p class="subtext">{{ __('Update student account details.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.students.index') }}">{{ __('Back to Students') }}</a>
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
                <form class="form-grid" action="{{ route('teacher.students.update', $student) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-field">
                        <label for="name">{{ __('Full name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $student->name) }}" required>
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="email">{{ __('Email address') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $student->email) }}" required>
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="phone">{{ __('Phone (optional)') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $student->phone) }}">
                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="school_class_id">{{ __('Class') }}</label>
                        <select id="school_class_id" name="school_class_id" required>
                            <option value="" disabled>{{ __('Select a class') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('school_class_id', $student->school_class_id) == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    @php($currentDepartment = old('department', $student->studentProfile?->department))
                    <div class="form-field">
                        <label for="department">{{ __('Department (optional)') }}</label>
                        <select id="department" name="department">
                            <option value="" @selected(!$currentDepartment)>{{ __('Select a department') }}</option>
                            @if ($currentDepartment && !$departments->contains('name', $currentDepartment))
                                <option value="{{ $currentDepartment }}" selected>{{ $currentDepartment }}</option>
                            @endif
                            @foreach ($departments as $department)
                                <option value="{{ $department->name }}" @selected($currentDepartment === $department->name)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="admission_id">{{ __('Admission ID (optional)') }}</label>
                        <input id="admission_id" name="admission_id" type="text" value="{{ old('admission_id', $student->studentProfile?->admission_id) }}">
                        @error('admission_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">{{ __('New password (optional)') }}</label>
                        <input id="password" name="password" type="password" autocomplete="new-password">
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">{{ __('Confirm new password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
