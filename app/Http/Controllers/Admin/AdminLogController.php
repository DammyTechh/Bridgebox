<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Services\AdminActionService;

class AdminLogController extends Controller
{
    public function index(AdminActionService $actionService)
    {
        $logs = AdminActionLog::with('user')->latest()->paginate(20);
        $actionsEnabled = $actionService->isEnabled();

        return view('admin.logs.index', [
            'logs' => $logs,
            'actionsEnabled' => $actionsEnabled,
        ]);
    }
}
