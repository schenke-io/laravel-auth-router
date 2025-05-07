<?php

use Laravel\Socialite\SocialiteServiceProvider;
use SchenkeIo\LaravelAuthRouter\AuthRouterServiceProvider;

return [
    AuthRouterServiceProvider::class,
    SocialiteServiceProvider::class,
];
