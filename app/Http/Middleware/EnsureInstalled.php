<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isInstalled()) {
            if ($request->is('install') || $request->is('install/*')) {
                return redirect()->route('landing');
            }

            return $next($request);
        }

        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        return redirect()->route('install.show');
    }

    private function isInstalled(): bool
    {
        return file_exists($this->lockPath());
    }

    private function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }
}
