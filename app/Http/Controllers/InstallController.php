<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use App\Models\Section;

class InstallController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('landing');
        }

        return view('install-mode');
    }

    public function showSchool(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('landing');
        }

        return view('install-school', [
            'sections' => $this->defaultSections(),
        ]);
    }

    public function showGeneric(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('landing');
        }

        return view('install-generic');
    }

    public function storeGeneric(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('landing');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'seed_demo' => ['nullable', 'boolean'],
            'import_source' => ['nullable', 'string', 'max:2000'],
        ]);

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        if (!empty($data['seed_demo']) || !empty($data['import_source'])) {
            try {
                // If an import source is provided, attempt to download & integrate via the GenericImportService
                if (!empty($data['import_source']) && class_exists(\App\Services\GenericImportService::class)) {
                    $importer = app(\App\Services\GenericImportService::class);
                    // the service expects a local extracted directory; the service can also accept a remote zip URL
                    // attempt to use its helper to fetch+integrate if available, otherwise download here
                    if (method_exists($importer, 'integrateImportFromUrl')) {
                        $importer->integrateImportFromUrl($data['import_source']);
                    } else {
                        // fallback: download & extract similar to the CLI command
                        $tmp = storage_path('app/imports/' . time());
                        if (!is_dir($tmp)) {
                            mkdir($tmp, 0755, true);
                        }
                        $zipPath = $tmp . '/import.zip';
                        $body = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])->get($data['import_source'])->body();
                        file_put_contents($zipPath, $body);
                        $zip = new \ZipArchive();
                        if ($zip->open($zipPath) === true) {
                            $zip->extractTo($tmp);
                            $zip->close();
                            $importer->integrateImport($tmp);
                        } else {
                            throw new \Exception('Failed to open downloaded import zip');
                        }
                    }
                }

                // run the seeder directly in this request so it uses the same DB connection and models
                if (class_exists(\Database\Seeders\DemoGenericSeeder::class)) {
                    app(\Database\Seeders\DemoGenericSeeder::class)->run();
                }
            } catch (\Exception $e) {
                return back()->withErrors(['install' => 'Generic install failed: ' . $e->getMessage()])->withInput();
            }
        }

        $this->writeLock($admin->email, 'generic');

        return redirect()->route('login', ['role' => 'admin'])->with('status', 'Installation completed.');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('landing');
        }

        if (!Schema::hasTable('users') || !Schema::hasTable('sections')) {
            return back()->withErrors([
                'install' => 'Database tables are missing. Please run migrations first.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string'],
            'demo_mode' => ['nullable', 'boolean'],
        ]);

        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        $this->seedSelectedSections($data['sections']);

        if (!empty($data['demo_mode'])) {
            app(\Database\Seeders\DemoAcademicSeeder::class)->run();
        }
        $this->writeLock($admin->email);

        return redirect()->route('login', ['role' => 'admin']);
    }

    private function defaultSections(): array
    {
        return [
            ['name' => 'Creche', 'slug' => 'creche', 'description' => 'Creche section'],
            ['name' => 'Kindergarten', 'slug' => 'kindergarten', 'description' => 'Kindergarten section'],
            ['name' => 'Primary', 'slug' => 'primary', 'description' => 'Primary section'],
            ['name' => 'Junior Secondary', 'slug' => 'junior-secondary', 'description' => 'Junior secondary section'],
            ['name' => 'Senior Secondary', 'slug' => 'senior-secondary', 'description' => 'Senior secondary section'],
            ['name' => 'University', 'slug' => 'university', 'description' => 'University section'],
        ];
    }

    private function seedSelectedSections(array $selectedSlugs): void
    {
        $sections = collect($this->defaultSections())
            ->keyBy('slug')
            ->only($selectedSlugs)
            ->values();

        foreach ($sections as $section) {
            Section::updateOrCreate(
                ['slug' => $section['slug']],
                [
                    'name' => $section['name'],
                    'description' => $section['description'],
                ]
            );
        }
    }

    private function isInstalled(): bool
    {
        return file_exists($this->lockPath());
    }

    private function writeLock(string $email, string $mode = 'school'): void
    {
        $path = $this->lockPath();
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = [
            'installed_at' => now()->toIso8601String(),
            'admin_email' => $email,
            'mode' => $mode,
        ];

        file_put_contents($path, json_encode($payload));
    }

    private function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }
} 
