<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Workbench\App\Console\MakeMarkdown;
use Workbench\App\Http\Middleware\SetLocaleFromCookie;

use function Orchestra\Testbench\default_skeleton_path;

return Application::configure(basePath: $APP_BASE_PATH ?? default_skeleton_path())
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withCommands([
        MakeMarkdown::class,
    ])

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['locale']);
        $middleware->append([
            SetLocaleFromCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
