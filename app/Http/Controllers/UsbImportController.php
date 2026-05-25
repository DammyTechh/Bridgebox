<?php

namespace App\Http\Controllers;

use App\Models\ImportedContent;
use App\Models\User;
use App\Services\UsbImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UsbImportController extends Controller
{
    public function __construct(private UsbImportService $service) {}

    /**
     * Lists currently mounted removable drives.
     * Used by both admin & teacher dashboards.
     */
    public function drives(): JsonResponse
    {
        $drives = $this->service->detectDrives();
        // Attach a quick inspect so the UI can show file counts immediately.
        $drives = array_map(function ($d) {
            $d['inspect'] = $this->service->inspectDrive($d['path']);
            return $d;
        }, $drives);

        return response()->json([
            'drives' => $drives,
            'clamav_available' => $this->service->isClamAvAvailable(),
            'job' => $this->service->currentJob(),
        ]);
    }

    /**
     * Starts a new import job.
     */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'drive' => 'required|string|max:1024',
        ]);

        $user = $request->user();
        // Only admin and teacher roles may trigger imports.
        if (! $user || ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_TEACHER], true)) {
            return response()->json(['success' => false, 'message' => 'Not authorised.'], 403);
        }

        $result = $this->service->startImport($data['drive'], $user->id);
        $status = $result['success'] ? 200 : 422;
        return response()->json($result, $status);
    }

    /**
     * Returns the current import job's progress.
     * Polled by the front-end every ~1s during a copy.
     */
    public function progress(): JsonResponse
    {
        $job = $this->service->currentJob();
        return response()->json(['job' => $job]);
    }

    /**
     * Lists imported content. All authenticated users can browse.
     * Students get a read-only view (no delete).
     */
    public function index(Request $request): JsonResponse
    {
        $items = ImportedContent::query()
            ->orderByDesc('imported_at')
            ->limit(200)
            ->get()
            ->map(fn (ImportedContent $c) => [
                'id' => $c->id,
                'name' => $c->original_name,
                'category' => $c->category,
                'extension' => $c->extension,
                'size' => $c->size_human,
                'size_bytes' => $c->size_bytes,
                'mime' => $c->mime_type,
                'url' => $c->public_url,
                'imported_at' => optional($c->imported_at)->toIso8601String(),
            ]);

        return response()->json(['items' => $items]);
    }

    /**
     * Deletes a single imported item. Admin & teacher only.
     */
    public function destroy(Request $request, ImportedContent $content): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_TEACHER], true)) {
            return response()->json(['success' => false, 'message' => 'Not authorised.'], 403);
        }

        $abs = storage_path('app/public/' . UsbImportService::LIBRARY_ROOT . '/' . $content->relative_path);
        if (is_file($abs)) {
            @unlink($abs);
        }
        $content->delete();

        return response()->json(['success' => true]);
    }
}
