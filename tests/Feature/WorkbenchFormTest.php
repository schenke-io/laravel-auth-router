<?php

use Workbench\App\Http\Controllers\SetLanguageController;

it('can submit the workbench form and see results', function () {
    $data = [
        'language' => 'de',
        'color' => 'Dark',
        'config_combo' => 'WhatsApp',
    ];

    $response = $this->post(route('workbench.store'), $data);

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('workbench_form_data', $data);
    $response->assertCookie(SetLanguageController::LANG_COOKIE, 'de', false);

    // Follow redirect
    $response = $this->get(route('home'));
    $response->assertStatus(200);
    $response->assertSee('Workbench Configuration');
    $response->assertSee('<option value="de" selected>Deutsch</option>', false);
    $response->assertSee('<option value="Dark" selected>Dark</option>', false);
    $response->assertSee('<option value="WhatsApp" selected>WhatsApp</option>', false);
    $response->assertSee('Go to WhatsApp Login');
});

it('shows the correct login link based on config combo', function (string $combo, string $routeName) {
    $data = [
        'language' => 'en',
        'color' => 'Light',
        'config_combo' => $combo,
    ];

    $this->post(route('workbench.store'), $data);

    $response = $this->get(route('home'));
    $response->assertSee(route($routeName));
})->with([
    ['WhatsApp', 'only-whatsapp.login'],
    ['Social', 'only-social.login'],
    ['Mix', 'mix.login'],
    ['Error', 'error.login'],
    ['Default', 'login'],
]);

it('applies the dark class when Dark is selected', function () {
    $data = [
        'language' => 'en',
        'color' => 'Dark',
        'config_combo' => 'Default',
    ];

    $this->post(route('workbench.store'), $data);

    $response = $this->get(route('home'));
    $response->assertSee('<html class="dark">', false);
});

it('does not apply the dark class when Light is selected', function () {
    $data = [
        'language' => 'en',
        'color' => 'Light',
        'config_combo' => 'Default',
    ];

    $this->post(route('workbench.store'), $data);

    $response = $this->get(route('home'));
    $response->assertDontSee('<html class="dark">', false);
});
