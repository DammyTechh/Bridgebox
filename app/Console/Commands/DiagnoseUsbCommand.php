<?php

namespace App\Console\Commands;

use App\Services\UsbImportService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Diagnostic command for USB drive detection.
 *
 * Run this from PowerShell when the dashboard says "No USB drive detected":
 *
 *     php artisan usb:diagnose
 *
 * It exercises every detection strategy and prints the results so you can
 * see exactly which one (if any) found your drive.
 */
class DiagnoseUsbCommand extends Command
{
    protected $signature = 'usb:diagnose';
    protected $description = 'Diagnose USB drive detection (run if dashboard shows no drives).';

    public function handle(UsbImportService $service): int
    {
        $this->info('=== BridgeBox USB Detection Diagnostic ===');
        $this->newLine();

        $isWindows = DIRECTORY_SEPARATOR === '\\' || str_starts_with(strtolower(PHP_OS), 'win');
        $this->line('Platform: ' . PHP_OS . ' (Windows: ' . ($isWindows ? 'yes' : 'no') . ')');
        $this->line('PHP version: ' . PHP_VERSION);
        $this->line('PHP binary: ' . PHP_BINARY);
        $this->newLine();

        if ($isWindows) {
            $this->diagnoseWindows();
        } else {
            $this->diagnoseLinux();
        }

        $this->newLine();
        $this->info('Final detectDrives() result:');
        $drives = $service->detectDrives();
        if (empty($drives)) {
            $this->error('  No drives returned. See checks above for why.');
        } else {
            foreach ($drives as $d) {
                $this->line(sprintf('  ✓ %s  (%s, %s)', $d['path'], $d['label'], $d['size_human']));
            }
        }

        $this->newLine();
        $this->info(' available: ' . ($service->isClamAvAvailable() ? 'yes' : 'no'));
        return self::SUCCESS;
    }

    private function diagnoseWindows(): void
    {
        // Strategy 1: Get-CimInstance via PowerShell
        $this->line('--- Strategy 1: PowerShell Get-CimInstance ---');
        $script = "Get-CimInstance Win32_LogicalDisk -Filter \"DriveType=2\" | Select-Object DeviceID,VolumeName,Size,FreeSpace | ConvertTo-Json -Compress";
        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE'));
        $p = Process::fromShellCommandline("powershell -NoProfile -EncodedCommand " . $encoded);
        $p->setTimeout(8);
        try { $p->run(); } catch (\Throwable $e) {
            $this->error('  Threw: ' . $e->getMessage());
        }
        $this->line('  Exit code: ' . $p->getExitCode());
        $out = trim($p->getOutput());
        $this->line('  Output: ' . ($out === '' ? '(empty)' : $out));
        if ($p->getErrorOutput()) {
            $this->line('  Stderr: ' . trim($p->getErrorOutput()));
        }
        $this->newLine();

        // Strategy 2: Get-Volume
        $this->line('--- Strategy 2: PowerShell Get-Volume (Removable) ---');
        $script2 = "Get-Volume | Where-Object { \$_.DriveType -eq 'Removable' -and \$_.DriveLetter } | ForEach-Object { [PSCustomObject]@{ DriveLetter=\$_.DriveLetter; Label=\$_.FileSystemLabel; Size=\$_.Size; Free=\$_.SizeRemaining } } | ConvertTo-Json -Compress";
        $encoded2 = base64_encode(mb_convert_encoding($script2, 'UTF-16LE'));
        $p2 = Process::fromShellCommandline("powershell -NoProfile -EncodedCommand " . $encoded2);
        $p2->setTimeout(8);
        try { $p2->run(); } catch (\Throwable $e) {
            $this->error('  Threw: ' . $e->getMessage());
        }
        $this->line('  Exit code: ' . $p2->getExitCode());
        $out2 = trim($p2->getOutput());
        $this->line('  Output: ' . ($out2 === '' ? '(empty)' : $out2));
        if ($p2->getErrorOutput()) {
            $this->line('  Stderr: ' . trim($p2->getErrorOutput()));
        }
        $this->newLine();

        // Strategy 3: Drive letter scan
        $this->line('--- Strategy 3: Drive letter brute-force (always works) ---');
        $systemDrive = strtoupper(getenv('SystemDrive') ?: 'C:');
        $systemLetter = $systemDrive[0] ?? 'C';
        $this->line('  System drive: ' . $systemDrive . ' (skipping ' . $systemLetter . ':)');
        $found = 0;
        foreach (range('A', 'Z') as $letter) {
            if ($letter === $systemLetter) continue;
            $path = $letter . ':\\';
            $exists = @is_dir($path);
            if (! $exists) continue;
            $total = @disk_total_space($path);
            if ($total === false || $total <= 0) {
                $this->line(sprintf('  %s exists but disk_total_space() failed', $path));
                continue;
            }
            $free = (int) @disk_free_space($path);
            $this->line(sprintf('  ✓ Found: %s  (%s total, %s free)',
                $path,
                $this->humanBytes((int) $total),
                $this->humanBytes($free)
            ));
            $found++;
        }
        if ($found === 0) {
            $this->warn('  No non-system drives found by brute-force.');
            $this->warn('  This means PHP cannot see any USB drive at all on this system.');
            $this->warn('  Possible causes:');
            $this->warn('    1. PHP CLI process running as a user that has no access to the drive.');
            $this->warn('    2. open_basedir restriction in php.ini blocking drive access.');
            $this->warn('    3. The USB drive uses a filesystem PHP cannot read (very rare).');
        }
    }

    private function diagnoseLinux(): void
    {
        $this->line('--- lsblk output ---');
        $p = Process::fromShellCommandline('lsblk -P -o NAME,RM,TYPE,MOUNTPOINT,SIZE,LABEL --noheadings');
        $p->setTimeout(5);
        try { $p->run(); } catch (\Throwable $e) {
            $this->error('  Threw: ' . $e->getMessage());
        }
        $this->line('  Exit code: ' . $p->getExitCode());
        $this->line('  Output:');
        foreach (explode("\n", trim($p->getOutput())) as $line) {
            $this->line('    ' . $line);
        }
        $this->newLine();

        $this->line('--- /media, /mnt, /run/media listings ---');
        foreach (['/media', '/mnt', '/run/media'] as $root) {
            if (! is_dir($root)) {
                $this->line('  ' . $root . ': does not exist');
                continue;
            }
            $entries = glob($root . '/*');
            if (empty($entries)) {
                $this->line('  ' . $root . ': empty');
                continue;
            }
            foreach ($entries as $e) {
                $this->line('  ' . $e . ' (' . (is_dir($e) ? 'dir' : 'file') . ')');
            }
        }
        $this->newLine();

        $this->line('--- clamscan availability ---');
        $p3 = Process::fromShellCommandline('which clamscan');
        $p3->setTimeout(3);
        try { $p3->run(); } catch (\Throwable $e) {}
        $this->line('  ' . (trim($p3->getOutput()) ?: 'NOT FOUND'));
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
