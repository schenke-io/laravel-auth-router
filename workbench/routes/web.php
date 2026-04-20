<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\SetLanguageController;
use Workbench\App\Http\Controllers\WorkbenchController;
use Workbench\App\Http\Middleware\ApplyWorkbenchPreferences;

Route::middleware(['web', ApplyWorkbenchPreferences::class])->group(function () {
    Route::get('/', [WorkbenchController::class, 'index'])->name('home');

    Route::authRouter(['google', 'facebook', 'amazon', 'microsoft', 'paypal'])
        ->success('success')->error('home')->home('home');

    Route::authRouter(['whatsapp'])
        ->success('success')->error('home')->home('home');

    Route::authRouter(['whatsapp', 'google', 'facebook'])
        ->success('success')->error('home')->home('home')
        ->prefix('only-whatsapp');

    Route::authRouter(['google', 'paypal', 'amazon', 'linkedin'])
        ->success('success')->error('home')->home('home')
        ->prefix('only-social');

    Route::authRouter(['whatsapp', 'separator', 'amazon', 'linkedin', 'apple'])
        ->success('success')->error('home')->home('home')
        ->prefix('mix');

    Route::authRouter(['google', 'unknown'])
        ->success('success')->error('home')->home('home')
        ->prefix('error');

    Route::post('/', [WorkbenchController::class, 'store'])->name('workbench.store');
});

Route::get('/set-lang/{lang}', SetLanguageController::class)->name('set-language');

Route::get('/success', function () {
    return view('auth-router::success');
})->name('success');

Route::get('/fake-socialite/{driver}', function (string $driver) {
    return view('workbench::fake-socialite', ['driver' => $driver]);
})->name('fake-socialite');

Route::get('/fake-socialite-callback/{driver}', function (string $driver) {
    return redirect("/callback/$driver?code=fake_code");
})->name('fake-socialite-callback');

// dd(config('services'));
