<?php


it('redirect an error and has text stored in a session', function () {
    Route::clearResolvedInstances();
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    Route::get('/error', function () {
        return Blade::render('test {{$error}} {{$codeError');
    })->name('error');
    Route::get('/home', function () {})->name('home');
    Route::get('/dashboard', function () {})->name('dashboard');

    Route::authRouter(['google'], 'dashboard', 'error', 'home');

    $response = $this->get('/callback/google');
    $response->assertRedirect(route('error'));
    $response->assertSessionHas('error');
    $response->assertSessionHas('codeError');

});