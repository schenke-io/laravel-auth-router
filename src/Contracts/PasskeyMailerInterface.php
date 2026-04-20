<?php

namespace SchenkeIo\LaravelAuthRouter\Contracts;

use Illuminate\Contracts\Mail\Mailable;

interface PasskeyMailerInterface extends Mailable
{
    public function sendMail(string $code): void;
}
