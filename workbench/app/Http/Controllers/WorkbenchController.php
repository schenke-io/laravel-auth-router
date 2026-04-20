<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Workbench\App\Services\SignInService;

/**
 * WorkbenchController handles the workbench start page and its configuration form.
 *
 * It allows users to choose language, color scheme, and auth router configuration combos,
 * storing these preferences in the session for testing and demonstration purposes.
 */
class WorkbenchController extends Controller
{
    public function index(Request $request, SignInService $signInService)
    {
        $sessionData = $request->session()->get('workbench_form_data', []);

        return view('workbench::welcome', [
            'signInService' => $signInService,
            'sessionData' => $sessionData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'language' => 'required|string',
            'color' => 'required|string',
            'config_combo' => 'required|string',
        ]);

        $request->session()->put('workbench_form_data', $data);

        $minutes = 60 * 24 * 365;
        $cookie = Cookie::make(SetLanguageController::LANG_COOKIE, $data['language'], $minutes, '/');

        return redirect()->route('home')->withCookie($cookie)->with('success', 'Form submitted successfully!');
    }
}
