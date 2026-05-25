@extends('admin.layout')

@section('title', ucfirst($type) . ' Questions')

@section('main')
    @php($routePrefix = $type === 'exam' ? 'admin.exams' : 'admin.quizzes')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ ucfirst($type) }}</p>
                <h1>{{ $assessment->title }}</h1>
                <p class="subtext">Manage questions for this {{ $type }}.</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route($routePrefix . '.questions.create', $assessment) }}">{{ __('Add Question') }}</a>
                <a class="btn ghost" href="{{ route($routePrefix . '.index') }}">Back to {{ ucfirst($type) }}s</a>
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
                <h4>{{ __('Questions') }}</h4>
                <span class="badge blue">{{ $questions->count() }}</span>
            </div>
            <div class="panel-body">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Question') }}</th>
                                <th>{{ __('Options') }}</th>
                                <th>{{ __('Answer') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($questions as $index => $question)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ Str::limit($question->prompt, 80) }}</td>
                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:4px;">
                                            @foreach ($question->options->sortBy('order') as $option)
                                                <span>{{ $option->option_text }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        @php($correct = $question->options->firstWhere('is_correct', true))
                                        {{ $correct?->option_text ?? '-' }}
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn ghost btn-small" href="{{ route($routePrefix . '.questions.edit', [$assessment, $question]) }}">{{ __('Edit') }}</a>
                                            <form method="post" action="{{ route($routePrefix . '.questions.delete', [$assessment, $question]) }}" data-confirm="{{ __('Delete this question?') }}" style="display:inline-block;">
                                                @csrf
                                                @method('delete')
                                                <button class="btn ghost btn-small" type="submit">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="table-empty" colspan="5">{{ __('No questions added yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
@endsection
