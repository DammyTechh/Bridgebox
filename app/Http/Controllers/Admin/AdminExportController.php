<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminExportController extends Controller
{
    public function assignments(Request $request, ExportService $exportService)
    {
        $format = $this->resolveFormat($request);

        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');

        $assignments = Assignment::query()
            ->with(['lesson.topic.subject', 'lesson.topic.schoolClass', 'submissions.user'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($classId || $subjectId || $topicId, function ($q) use ($classId, $subjectId, $topicId) {
                $q->whereHas('lesson.topic', function ($topicQuery) use ($classId, $subjectId, $topicId) {
                    if ($classId) {
                        $topicQuery->where('school_class_id', $classId);
                    }
                    if ($subjectId) {
                        $topicQuery->where('subject_id', $subjectId);
                    }
                    if ($topicId) {
                        $topicQuery->where('id', $topicId);
                    }
                });
            })
            ->orderBy('created_at')
            ->get();

        $headers = $this->assignmentHeaders();
        $rows = $this->assignmentRows($assignments);

        return $this->export(
            $exportService,
            $format,
            'assignments-export',
            $headers,
            $rows,
            'Assignments Export'
        );
    }

    public function assessments(Request $request, string $type, ExportService $exportService)
    {
        $this->assertType($type);
        $format = $this->resolveFormat($request);

        $search = $request->string('q')->trim()->toString();
        $classId = $request->integer('class_id');
        $subjectId = $request->integer('subject_id');
        $topicId = $request->integer('topic_id');
        $assessmentId = $request->integer('assessment_id');
        $studentId = $request->integer('student_id');

        $assessments = Assessment::query()
            ->with(['schoolClass', 'subject', 'topic', 'attempts.user'])
            ->where('type', $type)
            ->when($search !== '', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($classId, fn ($q) => $q->where('school_class_id', $classId))
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId))
            ->when($assessmentId, fn ($q) => $q->where('id', $assessmentId))
            ->orderBy('created_at')
            ->get();

        $headers = $this->assessmentHeaders();
        $rows = $this->assessmentRows($assessments, $studentId);

        $title = ucfirst($type) . ' Export';

        return $this->export(
            $exportService,
            $format,
            $type . '-export',
            $headers,
            $rows,
            $title
        );
    }

    private function resolveFormat(Request $request): string
    {
        $format = strtolower($request->string('format')->toString() ?: 'csv');
        if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            abort(404);
        }
        return $format;
    }

    private function export(
        ExportService $exportService,
        string $format,
        string $baseName,
        array $headers,
        array $rows,
        string $title
    ) {
        $timestamp = now()->format('Ymd_His');
        $filename = "{$baseName}_{$timestamp}.{$format}";

        if ($format === 'xlsx') {
            return $exportService->xlsxResponse($filename, $headers, $rows);
        }

        if ($format === 'pdf') {
            return $exportService->pdfResponse($filename, 'exports.table', [
                'title' => $title,
                'headers' => $headers,
                'rows' => $rows,
            ]);
        }

        return $exportService->csvResponse($filename, $headers, $rows);
    }

    private function assignmentHeaders(): array
    {
        return [
            'Assignment ID',
            'Title',
            'Class',
            'Subject',
            'Topic',
            'Lesson',
            'Due At',
            'Total Mark',
            'Pass Mark',
            'Retake Attempts',
            'Allow Late',
            'Late Mark',
            'Late Due At',
            'Submission ID',
            'Student Name',
            'Student Email',
            'Submitted At',
            'Status',
            'Score',
            'Feedback',
            'File Name',
            'Text Response',
        ];
    }

    private function assignmentRows(Collection $assignments): array
    {
        $rows = [];
        foreach ($assignments as $assignment) {
            $topic = $assignment->lesson?->topic;
            $base = [
                (string) $assignment->id,
                $assignment->title,
                $topic?->schoolClass?->name ?? '',
                $topic?->subject?->name ?? '',
                $topic?->title ?? '',
                $assignment->lesson?->title ?? '',
                $this->formatDate($assignment->due_at),
                (string) ($assignment->max_points ?? ''),
                (string) ($assignment->pass_mark ?? ''),
                (string) ($assignment->retake_attempts ?? ''),
                $assignment->allow_late ? 'Yes' : 'No',
                (string) ($assignment->late_mark ?? ''),
                $this->formatDate($assignment->late_due_at),
            ];

            $submissions = $assignment->submissions ?? collect();
            if ($submissions->isEmpty()) {
                $rows[] = array_merge($base, array_fill(0, 9, ''));
                continue;
            }

            foreach ($submissions as $submission) {
                $rows[] = array_merge($base, [
                    (string) $submission->id,
                    $submission->user?->name ?? '',
                    $submission->user?->email ?? '',
                    $this->formatDate($submission->submitted_at),
                    $submission->status ?? '',
                    (string) ($submission->score ?? ''),
                    $submission->feedback ?? '',
                    $submission->file_name ?? '',
                    $submission->content ?? '',
                ]);
            }
        }

        return $rows;
    }

    private function assessmentHeaders(): array
    {
        return [
            'Assessment ID',
            'Type',
            'Title',
            'Class',
            'Subject',
            'Topic',
            'Duration (min)',
            'Total Mark',
            'Pass Mark',
            'Retake Attempts',
            'Attempt ID',
            'Student Name',
            'Student Email',
            'Started At',
            'Completed At',
            'Status',
            'Score',
            'Total',
            'Result',
        ];
    }

    private function assessmentRows(Collection $assessments, ?int $studentId = null): array
    {
        $rows = [];
        foreach ($assessments as $assessment) {
            $base = [
                (string) $assessment->id,
                $assessment->type,
                $assessment->title,
                $assessment->schoolClass?->name ?? '',
                $assessment->subject?->name ?? '',
                $assessment->topic?->title ?? '',
                (string) ($assessment->time_limit_minutes ?? ''),
                (string) ($assessment->total_mark ?? ''),
                (string) ($assessment->pass_mark ?? ''),
                (string) ($assessment->retake_attempts ?? ''),
            ];

            $attempts = $assessment->attempts ?? collect();
            if ($studentId) {
                $attempts = $attempts->where('user_id', $studentId);
            }

            if ($attempts->isEmpty()) {
                $rows[] = array_merge($base, array_fill(0, 9, ''));
                continue;
            }

            foreach ($attempts as $attempt) {
                $score = $attempt->score ?? 0;
                $passMark = $assessment->pass_mark ?? 0;
                $result = $attempt->status === 'completed'
                    ? ($score >= $passMark ? 'Passed' : 'Needs Review')
                    : 'In Progress';

                $rows[] = array_merge($base, [
                    (string) $attempt->id,
                    $attempt->user?->name ?? '',
                    $attempt->user?->email ?? '',
                    $this->formatDate($attempt->started_at),
                    $this->formatDate($attempt->completed_at),
                    $attempt->status ?? '',
                    (string) ($attempt->score ?? ''),
                    (string) ($attempt->total ?? ''),
                    $result,
                ]);
            }
        }

        return $rows;
    }

    private function formatDate($value): string
    {
        return $value ? $value->format('Y-m-d H:i') : '';
    }

    private function assertType(string $type): void
    {
        if (!in_array($type, [Assessment::TYPE_QUIZ, Assessment::TYPE_EXAM], true)) {
            abort(404);
        }
    }
}
