<?php

use Workbench\App\Models\User;

beforeEach(function () {
    // any setup if needed
});

it('can access the home page', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);
});

it('can set language', function () {
    $response = $this->get(route('set-language', ['lang' => 'de']));
    $response->assertRedirect(route('home'));
    $response->assertCookie('locale', 'de', false);
});

it('can access the login page', function () {
    $response = $this->get(route('only-whatsapp.login'));
    $response->assertStatus(200);
});

it('can access the mix page', function () {
    $response = $this->get(route('mix.login'));
    $response->assertStatus(200);
});

it('can access the only-whatsapp page without errors', function () {
    $response = $this->get(route('only-whatsapp.login'));
    $response->assertStatus(200);
    $response->assertDontSee('<div class="error-message">', false);
});

it('can access the only-social page without errors', function () {
    $response = $this->get(route('only-social.login'));
    $response->assertStatus(200);
    $response->assertDontSee('<div class="error-message">', false);
});

it('can access the error page and it just works now', function () {
    $response = $this->get(route('error.login'));
    $response->assertStatus(200);
    $response->assertSee('<div class="error-message">', false);
    $response->assertSee('google');
});

it('can access the logout route when authenticated', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post(route('only-whatsapp.logout'));
    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

it('redirects to home if already logged in and visiting login', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('only-whatsapp.login'));
    $response->assertRedirect(route('home'));
});
