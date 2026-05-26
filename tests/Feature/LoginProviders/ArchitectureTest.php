<?php

pest()->group('feature');

arch('login providers')
    ->expect('App\LoginProviders')
    ->toExtend('SchenkeIo\LaravelAuthRouter\Auth\BaseProvider')
    ->toHaveSuffix('Provider')
    ->toHaveMethods(['login', 'callback', 'env']);
