<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Validator as LaravelValidator;
use Illuminate\Validation\Rules\Password;

/**
 * Validator class for authentication data.
 */
class Validator
{
    public static function validatePassword(string $password): ?string
    {
        $validator = LaravelValidator::make(
            ['password' => $password],
            ['password' => [
                'required',
                'string',
                Password::min(10)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ]]
        );

        if ($validator->fails()) {
            return $validator->errors()->first('password');
        }

        return null;
    }

    public static function isValidPassword(string $password): bool
    {
        return self::validatePassword($password) === null;
    }
}
