<?php

namespace App\Providers;

use App\Support\InstallMode;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InstallMode::class);
    }

    public function boot(): void
    {
        View::share('installMode', $this->app->make(InstallMode::class));
    }
}
