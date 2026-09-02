<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** @var list<string> */
    private const SUPPORTED = ['fr', 'en'];

    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, self::SUPPORTED, true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        return back()->withCookie(cookie()->forever('synoria_locale', $locale));
    }
}
