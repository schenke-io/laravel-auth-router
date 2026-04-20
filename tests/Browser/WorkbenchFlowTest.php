<?php

use SchenkeIo\LaravelAuthRouter\Tests\TestCase;

uses(TestCase::class);

it('follows the workbench browser flow', function () {
    // 1. Visit the workbench home page.
    $response = $this->get(route('home'));
    $response->assertStatus(200);
    $response->assertSee('Workbench Configuration');

    // 2. Change the language and color theme in the form.
    // 3. Submit the form.
    $data = [
        'language' => 'de',
        'color' => 'Dark',
        'config_combo' => 'Mix',
    ];

    $response = $this->post(route('workbench.store'), $data);
    $response->assertRedirect(route('home'));

    // Follow redirect
    $response = $this->get(route('home'));
    $response->assertSee('Workbench Configuration');
    $response->assertSee('<html class="dark">', false);
    $response->assertSee('<option value="de" selected>Deutsch</option>', false);

    // 4. Click "Go to Login".
    // On the home page, the link text is "Go to Mix Login" for Mix combo
    $response->assertSee('Go to Mix Login');

    // The link points to route('mix.login')
    $loginUrl = route('mix.login');
    $response = $this->get($loginUrl);
    $response->assertStatus(200);

    // 5. Assert that the login page matches the selected language and theme.
    // Theme check: <html class="dark">
    $response->assertSee('<html class="dark">', false);

    // Language check: Translate some strings
    // In German, "Willkommen zurück" is the title
    $response->assertSee('Willkommen zurück');
});
