<?php

namespace SchenkeIo\LaravelAuthRouter\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a back-channel logout request is received and validated.
 */
class BackChannelLogoutEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $provider  The name of the login provider (e.g., 'logto', 'auth0').
     * @param  string|null  $sub  The subject identifier of the user to log out.
     * @param  string|null  $sid  The session identifier to invalidate.
     */
    public function __construct(
        public string $provider,
        public ?string $sub,
        public ?string $sid
    ) {}
}
