@extends('teacher.layout')

@section('title', 'Create Class')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Teacher') }}</p>
                <h1>{{ __('Create Class') }}</h1>
                <p class="subtext">{{ __('Create a new class used in the system.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('teacher.classes.index') }}">{{ __('Back to Classes') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('Class Details') }}</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                <form class="form-grid" action="{{ route('teacher.classes.store') }}" method="post">
                    @csrf
                    <div class="form-field">
                        <label for="name">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="slug">{{ __('Slug (optional)') }}</label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug') }}">
                        @error('slug')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

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

                    <div class="form-field form-field-full">
                        <label for="description">{{ __('Description (optional)') }}</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Create Class') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
@endsection
