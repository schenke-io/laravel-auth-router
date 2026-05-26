<?php

pest()->group('feature');

use SchenkeIo\LaravelAuthRouter\Auth\Validator;

it('validates password correctly', function () {
    $validator = app(Validator::class);
    expect($validator->isValidPassword('Password123!'))->toBeTrue();
    expect($validator->isValidPassword('weak'))->toBeFalse();
    expect($validator->validatePassword('weak'))->toBeString();
});
