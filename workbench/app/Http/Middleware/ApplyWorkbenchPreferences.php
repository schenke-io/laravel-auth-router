<?php

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Http\Controllers\SetLanguageController;

class ApplyWorkbenchPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionData = $request->session()->get('workbench_form_data', []);

        $language = $request->cookie(SetLanguageController::LANG_COOKIE) ?? $sessionData['language'] ?? config('app.locale');
        $color = $sessionData['color'] ?? 'Light';
        // $configCombo = $sessionData['config_combo'] ?? 'default';

        App::setLocale($language);
        View::share('theme', $color);

        return $next($request);
    }
}
