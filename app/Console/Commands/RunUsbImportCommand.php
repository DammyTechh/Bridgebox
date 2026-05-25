<?php

namespace App\Console\Commands;

use App\Services\UsbImportService;
use Illuminate\Console\Command;

/**
 * Background worker invoked by UsbImportService::launchWorker.
 * Don't run this directly unless you know what you're doing.
 */
class RunUsbImportCommand extends Command
{
    protected $signature = 'usb:run-import
        {--job= : The unique job id}
        {--drive= : Absolute path of the USB drive to import from}
        {--user= : The user id triggering the import}';

    protected $description = 'Internal worker: copies files from a USB drive into the local library.';

    public function handle(UsbImportService $service): int
    {
        $jobId = (string) $this->option('job');
        $drive = (string) $this->option('drive');
        $userId = $this->option('user') !== null ? (int) $this->option('user') : null;

        if ($jobId === '' || $drive === '') {
            $this->error('Both --job and --drive are required.');
            return self::FAILURE;
        }

        $service->runImport($jobId, $drive, $userId);
        return self::SUCCESS;
    }
}
