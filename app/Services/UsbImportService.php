<?php

namespace App\Services;

use App\Models\ImportedContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * UsbImportService
 *
 * Detects removable USB drives and copies their contents into the local
 * library, organised by file type (mp4 -> video/, mp3 -> audio/, pdf -> document/, etc.).
 *
 * The virus-scan step has been removed at the request of the hardware engineer.
 * We rely on the BLOCKED_EXTENSIONS list to keep dangerous file types out.
 *
 * Cross-platform: Linux (Raspberry Pi production) AND Windows (developer testing).
 */
class UsbImportService
{
    public const LIBRARY_ROOT = 'library';

    /**
     * Dangerous file extensions that are never copied into the library,
     * even if the user's USB stick contains them.
     */
    private const BLOCKED_EXTENSIONS = [
        'exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'pif', 'vbs', 'vbe',
        'js', 'jse', 'wsf', 'wsh', 'ps1', 'psm1', 'jar', 'reg', 'dll',
        'sys', 'app', 'sh', 'bash',
    ];

    /**
     * Per-file size cap (2 GB).
     */
    private const MAX_FILE_SIZE = 2 * 1024 * 1024 * 1024;

    /**
     * Friendly label for each category folder (used in progress messages).
     */
    private const CATEGORY_FOLDERS = [
        'video'    => 'video folder',
        'audio'    => 'audio folder',
        'document' => 'document folder',
        'image'    => 'image folder',
        'archive'  => 'archive folder',
        'other'    => 'other folder',
    ];

    public function detectDrives(): array
    {
        if ($this->isWindows()) {
            return $this->detectDrivesWindows();
        }
        return $this->detectDrivesLinux();
    }

    /**
     * Quick stats about a USB drive: file count, total bytes, breakdown by category.
     * Logs a clear reason when the drive is unreadable so the engineer can fix permissions.
     */
    public function inspectDrive(string $drivePath): array
    {
        $drivePath = $this->normalisePath($drivePath);

        if (! is_dir($drivePath)) {
            return ['exists' => false, 'files' => 0, 'bytes' => 0, 'by_category' => [], 'error' => 'not_found'];
        }
        if (! is_readable($drivePath)) {
            Log::warning("USB inspect: drive {$drivePath} exists but is not readable by " . $this->currentProcessUser());
            return ['exists' => true, 'files' => 0, 'bytes' => 0, 'by_category' => [], 'error' => 'permission_denied'];
        }
        // Fast sanity check: can we list the top-level entries?
        $top = @scandir($drivePath);
        if ($top === false) {
            Log::warning("USB inspect: scandir failed on {$drivePath} for user " . $this->currentProcessUser());
            return ['exists' => true, 'files' => 0, 'bytes' => 0, 'by_category' => [], 'error' => 'permission_denied'];
        }

        $files = 0;
        $bytes = 0;
        $byCategory = [];

        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($drivePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($iter as $f) {
                if (! $f->isFile()) continue;
                if ($this->isBlockedExtension($f->getExtension())) continue;
                $files++;
                $bytes += $f->getSize();
                $cat = ImportedContent::categoryFromExtension($f->getExtension());
                $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
            }
        } catch (\Throwable $e) {
            Log::warning('USB inspect failed: ' . $e->getMessage());
        }

        return [
            'exists' => true,
            'files' => $files,
            'bytes' => $bytes,
            'size_human' => $this->humanBytes($bytes),
            'by_category' => $byCategory,
        ];
    }

    public function startImport(string $drivePath, ?int $userId = null): array
    {
        $drivePath = $this->normalisePath($drivePath);
        if (! is_dir($drivePath)) {
            return ['success' => false, 'message' => 'Drive not found: ' . $drivePath];
        }
        if (! is_readable($drivePath)) {
            return [
                'success' => false,
                'message' => "Drive {$drivePath} is mounted but is not readable by the web server (" . $this->currentProcessUser() . "). Ask the administrator to remount with read-everyone permissions, or run `sudo chmod -R a+rX " . escapeshellarg($drivePath) . "`.",
            ];
        }

        $current = $this->currentJob();
        if ($current && in_array($current['status'] ?? '', ['scanning', 'copying'], true)) {
            return ['success' => false, 'message' => 'An import is already in progress.'];
        }

        $jobId = (string) Str::uuid();
        $progressPath = $this->progressFilePath();

        $initial = [
            'job_id' => $jobId,
            'status' => 'starting',
            'message' => 'Preparing import…',
            'drive' => $drivePath,
            'user_id' => $userId,
            'total_files' => 0,
            'copied_files' => 0,
            'total_bytes' => 0,
            'copied_bytes' => 0,
            'current_file' => null,
            'current_category' => null,
            'current_folder' => null,
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'errors' => [],
        ];
        $this->writeJson($progressPath, $initial);

        $this->ensureLibraryDirs();
        $this->launchWorker($jobId, $drivePath, $userId);

        return ['success' => true, 'job_id' => $jobId];
    }

    /**
     * The actual copy loop. Normally invoked from a detached worker
     * (see launchWorker), but can be called inline for tests / debugging.
     */
    public function runImport(string $jobId, string $drivePath, ?int $userId = null): void
    {
        $progressPath = $this->progressFilePath();
        $update = function (array $patch) use ($progressPath) {
            $current = $this->readJson($progressPath) ?? [];
            $merged = array_merge($current, $patch);
            $this->writeJson($progressPath, $merged);
        };

        try {
            // --- Step 1: enumerate files ------------------------------------
            $update(['status' => 'scanning', 'message' => 'Reading files on USB…']);

            // Pre-flight permission check so we fail fast with a useful message
            if (! is_readable($drivePath) || @scandir($drivePath) === false) {
                $update([
                    'status' => 'error',
                    'message' => "Cannot read {$drivePath}. The web server (" . $this->currentProcessUser() . ") has no permission. Run: sudo chmod -R a+rX " . escapeshellarg($drivePath),
                    'finished_at' => now()->toIso8601String(),
                ]);
                return;
            }

            $files = $this->collectFiles($drivePath);
            if (empty($files)) {
                $update([
                    'status' => 'done',
                    'message' => 'No supported files found on the drive.',
                    'finished_at' => now()->toIso8601String(),
                ]);
                return;
            }
            $totalBytes = array_sum(array_column($files, 'size'));
            $update([
                'total_files' => count($files),
                'total_bytes' => $totalBytes,
            ]);

            // --- Step 2: copy each file (NO virus scan) ---------------------
            $update(['status' => 'copying', 'message' => 'Copying files…']);
            $copiedFiles = 0;
            $copiedBytes = 0;
            $errors = [];

            foreach ($files as $file) {
                try {
                    $info = $this->copySingleFile($file, $userId);
                    $copiedFiles++;
                    $copiedBytes += $file['size'];
                    $folder = self::CATEGORY_FOLDERS[$info['category']] ?? ($info['category'] . ' folder');
                    $update([
                        'copied_files' => $copiedFiles,
                        'copied_bytes' => $copiedBytes,
                        'current_file' => $info['original_name'],
                        'current_category' => $info['category'],
                        'current_folder' => $folder,
                        'message' => sprintf(
                            'Copying %s → %s (%d/%d)',
                            $info['original_name'],
                            $folder,
                            $copiedFiles,
                            count($files)
                        ),
                    ]);
                } catch (\Throwable $e) {
                    $errors[] = $file['name'] . ': ' . $e->getMessage();
                    $update(['errors' => $errors]);
                    Log::warning("USB copy failed for {$file['path']}: " . $e->getMessage());
                }
            }

            $update([
                'status' => 'done',
                'message' => sprintf('Imported %d of %d file(s).', $copiedFiles, count($files)),
                'finished_at' => now()->toIso8601String(),
                'current_file' => null,
                'current_category' => null,
                'current_folder' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('USB import job failed: ' . $e->getMessage());
            $update([
                'status' => 'error',
                'message' => 'Import failed: ' . $e->getMessage(),
                'finished_at' => now()->toIso8601String(),
            ]);
        }
    }

    public function currentJob(): ?array
    {
        return $this->readJson($this->progressFilePath());
    }

    /**
     * @deprecated Virus scanning has been removed. Kept for backwards
     * compatibility with the controller; always returns false.
     */
    public function isClamAvAvailable(): bool
    {
        return false;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function collectFiles(string $drivePath): array
    {
        $out = [];
        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($drivePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($iter as $f) {
                if (! $f->isFile()) continue;
                $ext = strtolower($f->getExtension());
                if ($this->isBlockedExtension($ext)) continue;
                $size = $f->getSize();
                if ($size <= 0 || $size > self::MAX_FILE_SIZE) continue;
                $out[] = [
                    'path' => $f->getRealPath() ?: $f->getPathname(),
                    'name' => $f->getFilename(),
                    'ext' => $ext,
                    'size' => $size,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('USB collectFiles iteration failed: ' . $e->getMessage());
        }
        return $out;
    }

    private function copySingleFile(array $file, ?int $userId): array
    {
        $ext = $file['ext'];
        $category = ImportedContent::categoryFromExtension($ext);
        $libRoot = storage_path('app/public/' . self::LIBRARY_ROOT);
        $catDir = $libRoot . DIRECTORY_SEPARATOR . $category;
        if (! is_dir($catDir)) {
            @mkdir($catDir, 0775, true);
        }

        $base = pathinfo($file['name'], PATHINFO_FILENAME);
        $slug = Str::slug($base) ?: 'file';
        $storedName = $slug . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $destAbs = $catDir . DIRECTORY_SEPARATOR . $storedName;

        $in = @fopen($file['path'], 'rb');
        if (! $in) throw new \RuntimeException('Cannot read source file.');
        $out = @fopen($destAbs, 'wb');
        if (! $out) {
            fclose($in);
            throw new \RuntimeException('Cannot create destination file.');
        }
        $hashCtx = hash_init('sha256');
        while (! feof($in)) {
            $chunk = fread($in, 1024 * 256);
            if ($chunk === false) break;
            fwrite($out, $chunk);
            hash_update($hashCtx, $chunk);
        }
        fclose($in);
        fclose($out);
        $hash = hash_final($hashCtx);

        $relativePath = $category . '/' . $storedName;

        $record = ImportedContent::create([
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'relative_path' => $relativePath,
            'category' => $category,
            'extension' => $ext,
            'mime_type' => $this->guessMime($destAbs, $ext),
            'size_bytes' => $file['size'],
            'hash_sha256' => $hash,
            'source_drive' => dirname($file['path']),
            // Virus scanning removed -> mark all files as "skipped" so the column is honest.
            'scan_status' => ImportedContent::SCAN_SKIPPED,
            'scan_message' => 'Virus scanning disabled.',
            'imported_by' => $userId,
            'imported_at' => now(),
        ]);

        return [
            'original_name' => $record->original_name,
            'category' => $record->category,
        ];
    }

    private function detectDrivesLinux(): array
    {
        $out = [];

        $p = Process::fromShellCommandline(
            'lsblk -P -o NAME,RM,TYPE,MOUNTPOINT,SIZE,LABEL --noheadings'
        );
        $p->setTimeout(5);
        try { $p->run(); } catch (\Throwable $e) {}
        if ($p->isSuccessful()) {
            foreach (explode("\n", trim($p->getOutput())) as $line) {
                if (! preg_match('/RM="(\d+)".*MOUNTPOINT="([^"]*)".*SIZE="([^"]*)".*LABEL="([^"]*)"/', $line, $m)) continue;
                if ($m[1] !== '1') continue;
                if ($m[2] === '' || $m[2] === '/') continue;
                $out[] = [
                    'path' => $m[2],
                    'label' => $m[4] !== '' ? $m[4] : basename($m[2]),
                    'size_human' => $m[3],
                    'free_human' => $this->humanBytes((int) @disk_free_space($m[2])),
                ];
            }
        }

        if (empty($out)) {
            foreach (['/media', '/mnt', '/run/media'] as $root) {
                if (! is_dir($root)) continue;
                foreach (glob($root . '/*') as $entry) {
                    if (! is_dir($entry)) continue;
                    foreach (glob($entry . '/*') ?: [$entry] as $candidate) {
                        if (is_dir($candidate)) {
                            $total = (int) @disk_total_space($candidate);
                            if ($total <= 0) continue;
                            $out[] = [
                                'path' => $candidate,
                                'label' => basename($candidate),
                                'size_human' => $this->humanBytes($total),
                                'free_human' => $this->humanBytes((int) @disk_free_space($candidate)),
                            ];
                        }
                    }
                }
            }
        }

        return $out;
    }

    private function detectDrivesWindows(): array
    {
        $out = $this->windowsDetectViaCim();
        if (! empty($out)) return $out;
        $out = $this->windowsDetectViaGetVolume();
        if (! empty($out)) return $out;
        return $this->windowsDetectByDriveLetter();
    }

    private function windowsDetectViaCim(): array
    {
        $script = "Get-CimInstance Win32_LogicalDisk -Filter \"DriveType=2\" | Select-Object DeviceID,VolumeName,Size,FreeSpace | ConvertTo-Json -Compress";
        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE'));
        $p = Process::fromShellCommandline("powershell -NoProfile -EncodedCommand " . $encoded);
        $p->setTimeout(8);
        try { $p->run(); } catch (\Throwable $e) { return []; }
        if (! $p->isSuccessful()) return [];
        $json = trim($p->getOutput());
        if ($json === '') return [];
        $data = json_decode($json, true);
        if ($data === null) return [];
        if (isset($data['DeviceID'])) $data = [$data];
        $out = [];
        foreach ($data as $row) {
            $deviceId = $row['DeviceID'] ?? '';
            if ($deviceId === '') continue;
            $out[] = [
                'path' => $deviceId . '\\',
                'label' => ($row['VolumeName'] ?? '') !== '' ? $row['VolumeName'] : $deviceId,
                'size_human' => $this->humanBytes((int) ($row['Size'] ?? 0)),
                'free_human' => $this->humanBytes((int) ($row['FreeSpace'] ?? 0)),
            ];
        }
        return $out;
    }

    private function windowsDetectViaGetVolume(): array
    {
        $script = "Get-Volume | Where-Object { \$_.DriveType -eq 'Removable' -and \$_.DriveLetter } | ForEach-Object { [PSCustomObject]@{ DriveLetter=\$_.DriveLetter; Label=\$_.FileSystemLabel; Size=\$_.Size; Free=\$_.SizeRemaining } } | ConvertTo-Json -Compress";
        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE'));
        $p = Process::fromShellCommandline("powershell -NoProfile -EncodedCommand " . $encoded);
        $p->setTimeout(8);
        try { $p->run(); } catch (\Throwable $e) { return []; }
        if (! $p->isSuccessful()) return [];
        $json = trim($p->getOutput());
        if ($json === '') return [];
        $data = json_decode($json, true);
        if ($data === null) return [];
        if (isset($data['DriveLetter'])) $data = [$data];
        $out = [];
        foreach ($data as $row) {
            $letter = $row['DriveLetter'] ?? '';
            if ($letter === '') continue;
            $out[] = [
                'path' => $letter . ':\\',
                'label' => ($row['Label'] ?? '') !== '' ? $row['Label'] : ($letter . ':'),
                'size_human' => $this->humanBytes((int) ($row['Size'] ?? 0)),
                'free_human' => $this->humanBytes((int) ($row['Free'] ?? 0)),
            ];
        }
        return $out;
    }

    private function windowsDetectByDriveLetter(): array
    {
        $out = [];
        $systemDrive = strtoupper(getenv('SystemDrive') ?: 'C:');
        $systemLetter = $systemDrive[0] ?? 'C';
        foreach (range('A', 'Z') as $letter) {
            if ($letter === $systemLetter) continue;
            $path = $letter . ':\\';
            if (! @is_dir($path)) continue;
            $total = @disk_total_space($path);
            if ($total === false || $total <= 0) continue;
            $free = (int) @disk_free_space($path);
            $label = $this->windowsVolumeLabel($letter) ?: ($letter . ':');
            $out[] = [
                'path' => $path,
                'label' => $label,
                'size_human' => $this->humanBytes((int) $total),
                'free_human' => $this->humanBytes($free),
            ];
        }
        return $out;
    }

    private function windowsVolumeLabel(string $letter): string
    {
        $p = Process::fromShellCommandline('cmd /c vol ' . $letter . ':');
        $p->setTimeout(2);
        try { $p->run(); } catch (\Throwable $e) { return ''; }
        if (! $p->isSuccessful()) return '';
        if (preg_match('/Volume in drive .* is (.+)/i', $p->getOutput(), $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function launchWorker(string $jobId, string $drivePath, ?int $userId): void
    {
        $php = $this->phpBinary();
        $artisan = base_path('artisan');
        $cmd = [
            $php, $artisan, 'usb:run-import',
            '--job=' . $jobId,
            '--drive=' . $drivePath,
        ];
        if ($userId) $cmd[] = '--user=' . $userId;

        $p = new Process($cmd, base_path());
        $p->setTimeout(null);
        $p->setIdleTimeout(null);
        try {
            if ($this->isWindows()) {
                $shell = sprintf(
                    'start "" /B %s',
                    implode(' ', array_map(fn($a) => '"' . str_replace('"', '\"', $a) . '"', $cmd))
                );
                $sp = Process::fromShellCommandline($shell, base_path());
                $sp->setTimeout(null);
                $sp->setIdleTimeout(null);
                $sp->disableOutput();
                $sp->start();
            } else {
                // Linux: detach via setsid so the worker outlives the request
                $shell = 'setsid ' . implode(' ', array_map(fn($a) => escapeshellarg($a), $cmd)) . ' > /dev/null 2>&1 &';
                $sp = Process::fromShellCommandline($shell, base_path());
                $sp->setTimeout(null);
                $sp->setIdleTimeout(null);
                $sp->disableOutput();
                $sp->start();
            }
        } catch (\Throwable $e) {
            Log::error('Failed to launch USB import worker: ' . $e->getMessage());
            // Last-ditch fallback: run inline (will block the HTTP response, but at least works)
            $this->runImport($jobId, $drivePath, $userId);
        }
    }

    private function ensureLibraryDirs(): void
    {
        $base = storage_path('app/public/' . self::LIBRARY_ROOT);
        if (! is_dir($base)) @mkdir($base, 0775, true);
        foreach (['video', 'audio', 'document', 'image', 'archive', 'other'] as $cat) {
            $d = $base . DIRECTORY_SEPARATOR . $cat;
            if (! is_dir($d)) @mkdir($d, 0775, true);
        }
        $publicLink = public_path('storage');
        if (! file_exists($publicLink)) {
            try {
                @symlink(storage_path('app/public'), $publicLink);
            } catch (\Throwable $e) { /* ignore */ }
        }
    }

    private function progressFilePath(): string
    {
        $dir = storage_path('app');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir . DIRECTORY_SEPARATOR . 'usb_import_progress.json';
    }

    private function readJson(string $path): ?array
    {
        if (! is_file($path)) return null;
        $raw = @file_get_contents($path);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function writeJson(string $path, array $data): void
    {
        @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function isBlockedExtension(string $ext): bool
    {
        return in_array(strtolower(ltrim($ext, '.')), self::BLOCKED_EXTENSIONS, true);
    }

    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\' || str_starts_with(strtolower(PHP_OS), 'win');
    }

    private function normalisePath(string $path): string
    {
        $trimmed = rtrim($path, "/\\");
        if ($this->isWindows() && preg_match('/^[A-Za-z]:$/', $trimmed)) {
            return $trimmed . '\\';
        }
        return $trimmed === '' ? $path : $trimmed;
    }

    private function phpBinary(): string
    {
        if (defined('PHP_BINARY') && PHP_BINARY) return PHP_BINARY;
        return $this->isWindows() ? 'php.exe' : 'php';
    }

    private function currentProcessUser(): string
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $info = @posix_getpwuid(@posix_geteuid());
            if (is_array($info) && isset($info['name'])) return $info['name'];
        }
        $env = getenv('USER') ?: getenv('USERNAME');
        return $env ?: 'unknown';
    }

    private function guessMime(string $path, string $ext): ?string
    {
        if (function_exists('mime_content_type')) {
            $m = @mime_content_type($path);
            if ($m) return $m;
        }
        $map = [
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mkv' => 'video/x-matroska',
            'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4',
            'pdf' => 'application/pdf', 'txt' => 'text/plain', 'csv' => 'text/csv',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'svg' => 'image/svg+xml',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip', '7z' => 'application/x-7z-compressed',
        ];
        return $map[$ext] ?? null;
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $b = (float) $bytes;
        while ($b >= 1024 && $i < count($units) - 1) {
            $b /= 1024;
            $i++;
        }
        return sprintf('%.1f %s', $b, $units[$i]);
    }
}
