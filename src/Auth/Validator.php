<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Rules\Password;

/**
 * Validator class for authentication data.
 */
class Validator
{
    public function __construct(protected ValidationFactory $factory) {}

    public function validatePassword(string $password): ?string
    {
        $validator = $this->factory->make(
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

    public function isValidPassword(string $password): bool
    {
        return $this->validatePassword($password) === null;
    }
}
