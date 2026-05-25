@extends('admin.layout')

@section('title', $installMode->isGeneric() ? __('Create Course') : __('Create Subject'))

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin') }}</p>
                <h1>{{ $installMode->isGeneric() ? __('Create Course') : __('Create Subject') }}</h1>
                <p class="subtext">{{ $installMode->isGeneric() ? __('Add a course to the learning library.') : __('Add a subject to a section.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.subjects.index') }}">{{ $installMode->isGeneric() ? __('Back to Courses') : __('Back to Subjects') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ $installMode->isGeneric() ? __('Course Details') : __('Subject Details') }}</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route('admin.subjects.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-field">
                        <label for="name">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($installMode->isSchool())
                    <div class="form-field">
                        <label for="section_id">{{ __('Section') }}</label>
                        <select id="section_id" name="section_id" required>
                            <option value="" disabled @selected(!old('section_id'))>{{ __('Select a section') }}</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </select>
                        @error('section_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    @endif

                    <div class="form-field form-field-full">
                        <label for="description">{{ __('Description (optional)') }}</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="feature_image">{{ __('Feature Image (optional)') }}</label>
                        <input id="feature_image" name="feature_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                        @error('feature_image')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ $installMode->isGeneric() ? __('Create Course') : __('Create Subject') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
