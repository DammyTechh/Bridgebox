<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class DemoGenericSeedCommand extends Command
{
    protected $signature = 'demo:generic-seed {--import=}';

    protected $description = 'Seed demo generic learning content. Optionally --import=<url> to download a zip of content.';

    public function handle(): int
    {
        $import = $this->option('import');

        if ($import) {
            $this->info('Downloading import from: ' . $import);
            try {
                // support basic Google Drive share links by converting them to direct download
                if (str_contains($import, 'drive.google.com')) {
                    // extract file id
                    if (preg_match('#/d/([a-zA-Z0-9_-]+)#', $import, $m)) {
                        $fileId = $m[1];
                        $import = "https://drive.google.com/uc?export=download&id={$fileId}";
                    }
                }

                $response = Http::withOptions(['verify' => false])->get($import);
                if (!$response->ok()) {
                    $this->error('Failed to download import: HTTP ' . $response->status());
                    return self::FAILURE;
                }
                $bytes = $response->body();
                $time = time();
                $dir = storage_path("app/imports/{$time}");
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $zipPath = $dir . '/import.zip';
                file_put_contents($zipPath, $bytes);
                $this->info('Saved import to ' . $zipPath);

                $zip = new ZipArchive();
                if ($zip->open($zipPath) === true) {
                    $zip->extractTo($dir);
                    $zip->close();
                    $this->info('Extracted import to ' . $dir);
                } else {
                    $this->error('Failed to open zip file');
                    return self::FAILURE;
                }

                // attempt to integrate extracted files into lessons
                $this->info('Import downloaded; attempting integration...');
                if (class_exists(\App\Services\GenericImportService::class)) {
                    try {
                        $importer = app(\App\Services\GenericImportService::class);
                        $importer->integrateImport($dir);
                        $this->info('Import integration completed.');
                    } catch (\Exception $e) {
                        $this->error('Import integration failed: ' . $e->getMessage());
                    }
                } else {
                    $this->info('GenericImportService not available; skipped integration.');
                }
            } catch (\Exception $e) {
                $this->error('Import failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info('Running DemoGenericSeeder...');
        if (class_exists(\Database\Seeders\DemoGenericSeeder::class)) {
            app(\Database\Seeders\DemoGenericSeeder::class)->run();
            $this->info('Seeding complete.');
            return self::SUCCESS;
        }

        $this->error('DemoGenericSeeder not found.');
        return self::FAILURE;
    }
}
