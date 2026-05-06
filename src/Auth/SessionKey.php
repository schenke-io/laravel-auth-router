<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

class SessionKey
{
    /**
     * Package-internal session keys.
     * These keys are used exclusively within this package and can be freely named.
     * All internal keys are prefixed with 'auth-router-'.
     */
    public const PROVIDER = 'auth-router-provider';

    public const PAYLOAD = 'auth-router-payload';

    public const ERROR_INFO = 'auth-router-error-info';

    public const ERROR_MESSAGE = 'auth-router-error-message';

    public const SUCCESS_MESSAGE = 'auth-router-success-message';

    public const PASSKEY_OTP = 'auth-router-passkey-otp';

    public const PASSKEY_EMAIL = 'auth-router-passkey-email';

    /**
     * Externally relevant session keys.
     * These keys are defined by external systems or Laravel standards and must maintain their naming.
     */

    /**
     * Prefix for Logto session storage.
     * Used by the Logto SDK to store authentication state, tokens, and other session-related data.
     */
    public const LOGTO_PREFIX = 'logto_';

    /**
     * Standard Laravel key for the intended URL after authentication.
     * Used by Laravel's built-in authentication system to redirect users back to their original destination.
     */
    public const URL_INTENDED = 'url.intended';
}
