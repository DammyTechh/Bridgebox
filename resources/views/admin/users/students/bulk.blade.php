@extends('admin.layout')

@section('title', 'Bulk Upload Students')

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin User Management') }}</p>
                <h1>{{ __('Bulk Upload Students') }}</h1>
                <p class="subtext">{{ __('Upload a CSV file to add students in one go.') }}</p>
            </div>
            <div class="actions">
                <a class="btn ghost" href="{{ route('admin.users.students.index') }}">{{ __('Back to Students') }}</a>
                <a class="btn ghost" href="{{ route('admin.users.students.create') }}">{{ __('Add Single Student') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if (session('message'))
            <div class="alert alert-dismissible {{ session('status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="5000">
                <span data-alert-message>{{ session('message') }}</span>
                <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="Dismiss alert">&times;</button>
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h4>{{ __('CSV Upload') }}</h4>
                <span class="badge gold">{{ __('Required') }}</span>
            </div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-field form-field-full">
                        <p class="subtext">{{ __('Download the sample CSV, fill it with your students, then upload it here.') }}</p>
                        <div class="actions">
                            <a class="btn ghost" href="{{ asset('assets/samples/students.csv') }}" download>{{ __('Download Sample CSV') }}</a>
                            <a class="btn ghost" href="{{ route('admin.classes.index') }}">{{ __('View Classes') }}</a>
                            <a class="btn ghost" href="{{ route('admin.departments.index') }}">{{ __('View Departments') }}</a>
                        </div>
                    </div>
                    <div class="form-field form-field-full">
                        <p class="subtext">Required headers: <code>name</code>, <code>email</code>, and either <code>school_class</code> or <code>school_class_id</code>.</p>
                        <p class="subtext">Optional headers: <code>phone</code>, <code>department</code>, <code>admission_id</code>, <code>password</code>, <code>auto_generate</code>.</p>
                        <p class="subtext">If <code>password</code> is blank and <code>auto_generate</code> is <code>1</code>, a password is generated and shown after import.</p>
                    </div>
                </div>

                <form class="form-grid" action="{{ route('admin.users.students.bulk.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-field">
                        <label for="csv_file">{{ __('CSV file') }}</label>
                        <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" required>
                        @error('csv_file')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-actions">
                        <button class="btn primary" type="submit">{{ __('Upload Students') }}</button>
                    </div>
                </form>
            </div>
        </section>

        @if (session('bulk_errors'))
            <section class="panel">
                <div class="panel-header">
                    <h4>{{ __('Import Issues') }}</h4>
                    <span class="badge rose">{{ __('Needs Attention') }}</span>
                </div>
                <div class="panel-body">
                    <ul class="list">
                        @foreach (session('bulk_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        @if (session('bulk_created'))
            <section class="panel table-panel">
                <div class="panel-header">
                    <h4>{{ __('Created Students (Passwords shown once)') }}</h4>
                    <span class="badge blue">{{ count(session('bulk_created')) }}</span>
                </div>
                <div class="panel-body">
                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Class') }}</th>
                                    <th>{{ __('Password') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (session('bulk_created') as $student)
                                    <tr>
                                        <td>{{ $student['name'] }}</td>
                                        <td>{{ $student['email'] }}</td>
                                        <td>{{ $student['class'] ?? '-' }}</td>
                                        <td>{{ $student['password'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif
    </main>
@endsection
