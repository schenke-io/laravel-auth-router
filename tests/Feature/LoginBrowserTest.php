<?php

use Illuminate\Support\Facades\Route;

it('renders the login page correctly with different session data', function (string $color, string $expectedClass, string $locale, string $expectedTitle) {
    // Register routes
    Route::authRouter(['google', 'github'])->home('home');

    // Set application locale to match our test case
    app()->setLocale($locale);

    // Simulate the session data and make the request
    $response = $this->withSession([
        'workbench_form_data.color' => $color,
        'locale' => $locale,
    ])->get(route('login'));

    $response->assertStatus(200);

    // Check for the correct class on the html tag
    if ($expectedClass) {
        $response->assertSee("<html class=\"$expectedClass\">", false);
    } else {
        $response->assertSee('<html class="">', false);
    }

    // Check for the translated title
    $response->assertSee($expectedTitle);
})->with([
    ['Light', '', 'en', 'Welcome back'],
    ['Dark', 'dark', 'en', 'Welcome back'],
    ['Light', '', 'de', 'Willkommen zurück'],
    ['Dark', 'dark', 'de', 'Willkommen zurück'],
]);
