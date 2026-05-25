<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check()) {
            return redirect()->route('login', ['role' => $role]);
        }

        $user = Auth::user();
        if (!$user || $user->role !== $role) {
            Auth::logout();
            return redirect()->route('login', ['role' => $role])
                ->with('error', 'Please sign in with the correct role.');
        }

        return $next($request);
    }
}
