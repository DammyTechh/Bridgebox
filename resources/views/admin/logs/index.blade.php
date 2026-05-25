@extends('admin.layout')

@section('title', __('Admin Logs'))

@php
    $actionsEnabled = $actionsEnabled ?? false;
    $actionsDisabled = ! $actionsEnabled;
@endphp

@section('main')
    <main class="main">
        <header class="topbar">
            <div class="greeting">
                <p class="eyebrow">{{ __('Admin') }}</p>
                <h1>{{ __('Admin Logs') }}</h1>
                <p class="subtext">{{ __('Review recent system actions and results.') }}</p>
            </div>
            <div class="actions">
                <form data-admin-action data-action-name="clear_logs" data-confirm="{{ __('Clear all admin action logs?') }}" action="{{ route('dashboard.admin.actions', ['action' => 'clear_logs']) }}" method="post">
                    @csrf
                    <button class="btn ghost" type="submit" @disabled($actionsDisabled)>{{ __('Clear Logs') }}</button>
                </form>
                <a class="btn ghost" href="{{ route('dashboard.admin') }}">{{ __('Back to Dashboard') }}</a>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="btn primary" type="submit">{{ __('Logout') }}</button>
                </form>
            </div>
        </header>

        @if (session('action_message'))
            <div class="alert alert-dismissible {{ session('action_status') === 'success' ? 'alert-success' : 'alert-error' }}" role="status" data-auto-dismiss="4000">
                <span data-alert-message>{{ session('action_message') }}</span>
                <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="Dismiss alert">&times;</button>
            </div>
        @endif

        <div class="alert alert-dismissible" id="action-alert" role="status" hidden>
            <span data-alert-message></span>
            <button class="alert-close" type="button" data-alert-close data-bs-dismiss="alert" aria-label="Dismiss alert">&times;</button>
        </div>

        <section class="panel table-panel">
            <div class="panel-header">
                <h4>{{ __('Action Logs') }}</h4>
                <span class="badge blue">{{ $logs->total() }}</span>
            </div>
            <div class="panel-body table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('Time') }}</th>
                            <th>{{ __('Admin') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Result') }}</th>
                            <th>{{ __('Message') }}</th>
                        </tr>
                    </thead>
                    <tbody data-action-log-body>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->user->name ?? 'Unknown' }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ ucfirst($log->result) }}</td>
                                <td>{{ $log->message }}</td>
                            </tr>
                        @empty
                            <tr data-action-log-empty>
                                <td colspan="5">{{ __('No actions logged yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.users.partials.pagination', ['paginator' => $logs])
        </section>
    </main>
@endsection
