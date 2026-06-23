<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchModeController extends Controller
{
    /**
     * Switch the installation mode between 'school' and 'generic'.
     * Only accessible by authenticated admins.
     */
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'string', 'in:school,generic'],
        ]);

        $lockPath = storage_path('app/installed.lock');

        if (!file_exists($lockPath)) {
            return redirect()->route('landing')->withErrors(['mode' => 'System is not installed.']);
        }

        $lock = json_decode(file_get_contents($lockPath), true) ?: [];
        $lock['mode'] = $data['mode'];
        $lock['mode_switched_at'] = now()->toIso8601String();

        file_put_contents($lockPath, json_encode($lock));

        return redirect()->route('landing')->with('status', 'Mode switched to ' . ucfirst($data['mode']) . '.');
    }
}
