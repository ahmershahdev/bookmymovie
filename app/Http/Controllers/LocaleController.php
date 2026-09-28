<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /** Switches between English and Urdu and remembers the choice. */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', Rule::in(SetLocale::SUPPORTED)]]);

        $request->session()->put('locale', $data['locale']);
        Cookie::queue('bmm_locale', $data['locale'], 60 * 24 * 365);
        $request->user()?->forceFill(['locale' => $data['locale']])->save();

        return back();
    }
}
