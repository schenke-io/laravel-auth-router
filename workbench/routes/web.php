<?php

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Workbench\app\Repository\DummyProvider;
use Workbench\App\Http\Controllers\SetLanguageController;
use Workbench\App\Http\Middleware\SetLocaleFromCookie;

Route::get('/', function () {
    return view('workbench::welcome', ['provider' => new DummyProvider]);
})->name('home')->middleware(SetLocaleFromCookie::class);

Route::get('/set-lang/{lang}', SetLanguageController::class)->name('set-language');

Route::authRouter(['google', 'dd', 'postmark'], 'home', 'home', 'home');

// dd(config('services'));
