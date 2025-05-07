<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SetLanguageController
{
    public const LANG_COOKIE = 'locale';

    public function __invoke(Request $request, string $lang): RedirectResponse
    {
        $minutes = 60 * 24 * 365;

        // Redirect to the homepage ('/') and attach the cookie
        return redirect('/')->withCookie(Cookie::make(self::LANG_COOKIE, $lang, $minutes, '/'));
    }
}
