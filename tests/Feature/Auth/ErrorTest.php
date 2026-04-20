<?php

it('redirect an error and has text stored in a session', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    Route::get('/error', function () {
        return Blade::render('test {{$error}} {{$codeError');
    })->name('error');
    Route::get('/home', function () {})->name('home');
    Route::get('/dashboard', function () {})->name('dashboard');

    app('router')->getRoutes()->refreshNameLookups();

    Route::authRouter(['google'])->success('dashboard')->error('error')->home('home');
    // dd(routeNames());

    $response = $this->get('/callback/google');
    $response->assertRedirect(route('error'));
    $response->assertSessionHas('authRouterErrorInfo');
    $response->assertSessionHas('authRouterErrorMessage');

});
