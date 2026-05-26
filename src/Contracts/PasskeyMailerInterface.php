<?php

namespace SchenkeIo\LaravelAuthRouter\Contracts;

use Illuminate\Contracts\Mail\Mailable;

/**
 * Interface PasskeyMailerInterface
 *
 * Defines the contract for mailing passkey authentication codes.
 */
interface PasskeyMailerInterface extends Mailable
{
    public function sendMail(string $code): void;
}
