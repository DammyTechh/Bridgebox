<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminActionLog;
use App\Services\AdminActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AdminActionController extends Controller
{
    public function run(Request $request, string $action, AdminActionService $service): RedirectResponse|JsonResponse
    {
        try {
            $result = $service->execute($action);
        } catch (\Throwable $exception) {
            $result = [
                'success' => false,
                'message' => 'Action failed: ' . $exception->getMessage(),
            ];
        }

        $logsCleared = false;
        if ($action === 'clear_logs' && $result['success']) {
            AdminActionLog::query()->delete();
            $logsCleared = true;
        }

        $log = AdminActionLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'result' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
        ]);

        if ($request->expectsJson()) {
            $log->load('user');
            $payload = [
                'success' => $result['success'],
                'message' => $result['message'],
                'logs_cleared' => $logsCleared,
                'log' => [
                    'time' => $log->created_at->format('Y-m-d H:i'),
                    'user' => $log->user->name ?? 'Unknown',
                    'action' => $log->action,
                    'result' => ucfirst($log->result),
                    'message' => $log->message,
                ],
            ];

            return response()->json($payload, $result['success'] ? 200 : 422);
        }

        return back()->with([
            'action_status' => $result['success'] ? 'success' : 'error',
            'action_message' => $result['message'],
        ]);
    }
}
