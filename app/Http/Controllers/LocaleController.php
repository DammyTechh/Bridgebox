<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'locale' => ['required', 'in:en,ha'],
        ]);

        $user = $request->user();
        $user->locale = $request->input('locale');
        $user->save();

        return redirect()->back();
    }
}
