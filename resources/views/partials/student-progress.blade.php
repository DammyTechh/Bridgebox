<section class="quick-tabs">
    <div class="tab" style="--accent: #4a7bd1; --d: 0.05s;">
        <div class="tab-icon">
            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
        </div>
        <div>
            <p>Lessons</p>
            <span>{{ $lessonsCount }}</span>
        </div>
    </div>
    <div class="tab" style="--accent: #e56b6f; --d: 0.1s;">
        <div class="tab-icon">
            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
        </div>
        <div>
            <p>Topics</p>
            <span>{{ $topicsCount }}</span>
        </div>
    </div>
    <div class="tab" style="--accent: #f2b84b; --d: 0.15s;">
        <div class="tab-icon">
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
        </div>
        <div>
            <p>Assignments</p>
            <span>{{ $submissionsCount }} / {{ $assignmentsCount }}</span>
        </div>
    </div>
    <div class="tab" style="--accent: #56c1a7; --d: 0.2s;">
        <div class="tab-icon">
            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
        </div>
        <div>
            <p>Assessments</p>
            <span>{{ $attemptsCompleted }} / {{ $quizzesCount + $examsCount }}</span>
        </div>
    </div>
</section>

<section class="panel table-panel">
    <div class="panel-header">
        <h4>Recent Assessments</h4>
        <span class="badge blue">{{ $recentAttempts->count() }}</span>
    </div>
    <div class="panel-body">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Assessment</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Topic</th>
                        <th>Score</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAttempts as $attempt)
                        @php($routeName = ($attempt->assessment?->type ?? 'quiz') === 'exam' ? $attemptReviewRouteExam : $attemptReviewRouteQuiz)
                        <tr>
                            <td>{{ $attempt->assessment?->title ?? '-' }}</td>
                            <td>{{ ucfirst($attempt->assessment?->type ?? '-') }}</td>
                            <td>{{ $attempt->assessment?->subject?->name ?? '-' }}</td>
                            <td>{{ $attempt->assessment?->topic?->title ?? '-' }}</td>
                            <td>{{ $attempt->score ?? 0 }} / {{ $attempt->total ?? 0 }}</td>
                            <td>{{ $attempt->completed_at?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                @if ($attempt->assessment)
                                    <a class="btn ghost btn-small" href="{{ route($routeName, $attempt) }}">Review</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="table-empty" colspan="7">No assessment attempts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="panel table-panel">
    <div class="panel-header">
        <h4>Recent Assignment Submissions</h4>
        <span class="badge blue">{{ $recentSubmissions->count() }}</span>
    </div>
    <div class="panel-body">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Assignment</th>
                        <th>Lesson</th>
                        <th>Topic</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentSubmissions as $submission)
                        <tr>
                            <td>{{ $submission->assignment?->title ?? '-' }}</td>
                            <td>{{ $submission->assignment?->lesson?->title ?? '-' }}</td>
                            <td>{{ $submission->assignment?->lesson?->topic?->title ?? '-' }}</td>
                            <td>{{ $submission->status ?? 'submitted' }}</td>
                            <td>{{ $submission->score ?? '-' }}</td>
                            <td>{{ $submission->submitted_at?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                @if ($submission->assignment)
                                    <a class="btn ghost btn-small" href="{{ route($submissionReviewRoute, [$submission->assignment, $submission]) }}">Review</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="table-empty" colspan="7">No submissions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
