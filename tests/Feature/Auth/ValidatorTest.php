<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Auth;

use SchenkeIo\LaravelAuthRouter\Auth\Validator;

it('validates password correctly', function () {
    expect(Validator::isValidPassword('Password123!'))->toBeTrue();
    expect(Validator::isValidPassword('weak'))->toBeFalse();
    expect(Validator::validatePassword('weak'))->toBeString();
});
