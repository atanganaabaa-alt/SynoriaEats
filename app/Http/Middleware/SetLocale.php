<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?? $request->cookie('synoria_locale')
            ?? config('app.locale', 'fr');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'fr';
        }

        App::setLocale($locale);

        $response = $next($request);

        return $response->withCookie(cookie()->forever('synoria_locale', $locale));
    }
}
