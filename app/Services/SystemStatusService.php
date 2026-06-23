<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class SystemStatusService
{
    private const CACHE_KEY = 'admin.system_status';
    private const CACHE_TTL_SECONDS = 7;

    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $hotspot = $this->getHotspotConnection();
            $uptimeSeconds = $this->getUptimeSeconds();

            return [
                'server' => $this->getServerStatus(),
                'hotspot' => $this->formatHotspotStatus($hotspot),
                'devices' => $this->getConnectedDevices($hotspot),
                'app_health' => $this->getAppHealth(),
                'storage' => $this->getStorage(),
                'power' => $this->getPowerHealth(),
                'uptime' => $this->formatDuration($uptimeSeconds),
                'uptime_seconds' => $uptimeSeconds,
                'last_update' => $this->getLastUpdate(),
            ];
        });
    }

    private function getServerStatus(): string
    {
        $nginxActive = $this->isServiceActive(['nginx', 'nginx.service']);
        $phpActive = $this->isServiceActive(['php-fpm', 'php8.2-fpm', 'php8.1-fpm', 'php8.0-fpm', 'php8.4-fpm']);

        if ($nginxActive === null) {
            $nginxActive = $this->isProcessRunning(['nginx']);
        }

        if ($phpActive === null) {
            $phpActive = $this->isProcessRunning(['php-fpm', 'php-fpm8.2', 'php-fpm8.1', 'php-fpm8.0', 'php-fpm8.4'], true);
        }

        if ($nginxActive === null && $phpActive === null) {
            return 'Unknown';
        }

        if ($nginxActive && $phpActive) {
            return 'Running';
        }

        return 'Stopped';
    }

    private function getHotspotConnection(): ?array
    {
        $output = $this->runCommand('iw dev wlan0 info');

        if ($output && str_contains($output, 'type AP')) {
            return [
		'name' => 'BridgeBox_AP',
		'ssid' => 'BridgeBox_AP',
		'device' => 'wlan0'

		];
        }

        $lines = array_filter(explode("\n", trim($output)));
        foreach ($lines as $line) {
            $parts = explode(':', $line);
            $name = $parts[0] ?? '';
            $type = $parts[1] ?? '';
            $device = $parts[2] ?? '';
            $active = $parts[3] ?? '';
            $uuid = $parts[4] ?? '';

            if ($active !== 'yes' || !$this->isWifiConnectionType($type) || $uuid === '') {
                continue;
            }

            $modeOutput = $this->runCommand("nmcli -t -f 802-11-wireless.mode,802-11-wireless.ssid con show {$uuid}");
            if ($modeOutput === null) {
                continue;
            }

            $modeParts = explode(':', trim($modeOutput), 2);
            $mode = strtolower($modeParts[0] ?? '');
            $ssid = $modeParts[1] ?? '';

            if ($mode === 'ap') {
                return [
                    'name' => $name,
                    'ssid' => $ssid,
                    'device' => $device,
                ];
            }
        }

        return null;
    }

    private function formatHotspotStatus(?array $hotspot): string
    {
        if ($hotspot === null) {
            return 'Off';
        }

        $ssid = $hotspot['ssid'] ?? '';
        $name = $hotspot['name'] ?? '';

        if ($ssid !== '') {
            return "On · {$ssid}";
        }

        return $name !== '' ? "On · {$name}" : 'On';
    }

    private function getConnectedDevices(?array $hotspot): string
    {
        if ($hotspot === null) {
            return '0';
        }

        $device = $hotspot['device'] ?? 'wlan0';
        $output = $this->runCommand("iw dev {$device} station dump");
        if ($output === null) {
            return 'Unknown';
        }

        $count = 0;
        foreach (explode("\n", $output) as $line) {
            if (str_starts_with(trim($line), 'Station')) {
                $count++;
            }
        }

        return (string) $count;
    }

    private function getAppHealth(): string
    {
        $queue = config('queue.default', 'sync');
        $queueLabel = $queue === 'sync' ? 'Queue: sync' : "Queue: {$queue}";

        $logPath = storage_path('logs/laravel.log');
        if (is_file($logPath)) {
            $lastError = date('Y-m-d H:i', filemtime($logPath));
            return "{$queueLabel} · Last error: {$lastError}";
        }

        return "{$queueLabel} · Last error: none";
    }

    private function getStorage(): string
    {
        $path = base_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false) {
            return 'Unknown';
        }

        return sprintf('%s free / %s total', $this->formatBytes($free), $this->formatBytes($total));
    }

    /**
     * Read battery percentage (and optionally voltage) from the hardware UART
     * data file that the Raspberry Pi firmware keeps updated at /tmp/uart_data.txt.
     *
     * Expected file format (one key=value per line, order not guaranteed):
     *   VOLT=3.92
     *   BATT=78
     *
     * BATT is the battery percentage (0-100).
     * VOLT is the raw cell voltage (used as a fallback label only).
     */
     
  private function getPowerHealth(): string
{
    $path = '/tmp/uart_data.json';

    if (!is_readable($path)) {
        return 'Unknown';
    }

    $contents = file_get_contents($path);
    if ($contents === false || trim($contents) === '') {
        return 'Unknown';
    }

    $data = json_decode($contents, true);

    if (!is_array($data)) {
        return 'Unknown';
    }

    $batt = $data['battery'] ?? null;
    $volt = $data['voltage'] ?? null;

    if ($batt !== null) {
        $pct = (int) max(0, min(100, round($batt)));

        if ($volt !== null) {
            return "{$pct}% · " . number_format((float)$volt, 2) . 'V';
        }

        return "{$pct}%";
    }

    if ($volt !== null) {
        return number_format((float)$volt, 2) . 'V';
    }

    return 'Unknown';
}


    private function getUptime(): string
    {
        $seconds = $this->getUptimeSeconds();
        return $this->formatDuration($seconds);
    }

    private function getUptimeSeconds(): ?int
    {
        $procUptime = '/proc/uptime';
        if (is_file($procUptime)) {
            $contents = trim((string) file_get_contents($procUptime));
            $parts = explode(' ', $contents);
            if (!empty($parts[0])) {
                return (int) floor((float) $parts[0]);
            }
        }

        $output = $this->runCommand('cat /proc/uptime');
        if ($output) {
            $parts = explode(' ', trim($output));
            if (!empty($parts[0])) {
                return (int) floor((float) $parts[0]);
            }
        }

        return null;
    }

    private function getLastUpdate(): string
    {
        $versionPath = base_path('VERSION');
        if (is_file($versionPath)) {
            $version = trim((string) file_get_contents($versionPath));
            return $version !== '' ? $version : 'Unknown';
        }

        return 'Unknown';
    }

    private function isServiceActive(array $serviceNames): ?bool
    {
        foreach ($serviceNames as $service) {
            $output = $this->runCommand("systemctl is-active {$service}");
            if ($output === null) {
                continue;
            }

            $status = trim($output);
            if ($status === 'active') {
                return true;
            }

            if (in_array($status, ['inactive', 'failed', 'deactivating'], true)) {
                return false;
            }
        }

        return null;
    }

    private function isProcessRunning(array $names, bool $fuzzy = false): ?bool
    {
        foreach ($names as $name) {
            $command = $fuzzy ? "pgrep -f {$name}" : "pgrep -x {$name}";
            $output = $this->runCommand($command);
            if ($output === null) {
                continue;
            }

            if (trim($output) !== '') {
                return true;
            }
        }

        return null;
    }

    private function isWifiConnectionType(string $type): bool
    {
        $type = strtolower($type);
        return $type === 'wifi'
            || str_contains($type, 'wireless')
            || str_contains($type, '802-11');
    }

    private function runCommand(string $command): ?string
    {
        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(2);
            $process->run();

            if (!$process->isSuccessful()) {
                return null;
            }

            return trim($process->getOutput());
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return sprintf('%.1f %s', $bytes, $units[$index]);
    }

    private function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return 'Unknown';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $minutes . 'm';
        $parts[] = $secs . 's';

        return implode(' ', $parts);
    }
}
