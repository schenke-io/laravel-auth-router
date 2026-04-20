<?php

use Laravel\Socialite\SocialiteServiceProvider;
use SchenkeIo\LaravelAuthRouter\AuthRouterServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

return [
    AuthRouterServiceProvider::class,
    SocialiteServiceProvider::class,
    WorkbenchServiceProvider::class,
];
