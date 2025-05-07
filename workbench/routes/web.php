<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\SetLanguageController;
use Workbench\App\Http\Middleware\SetLocaleFromCookie;

Route::get('/', function () {
    return view('workbench::welcome');
})->name('home')->middleware(SetLocaleFromCookie::class);

Route::get('/set-lang/{lang}', SetLanguageController::class)->name('set-language');

Route::authRouter(['google', 'dd'], 'home', 'home', 'home');
