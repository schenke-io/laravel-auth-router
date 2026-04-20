<?php

namespace SchenkeIo\LaravelAuthRouter\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;

/**
 * Service to handle Apple-specific authentication tasks,
 * like Server-to-Server notifications.
 */
class AppleAuthService
{
    /**
     * Handles Apple Server-to-Server Notifications
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleServerNotification(array $payload): void
    {
        // Apple sends a signed 'payload' string which is a JWT
        $token = $payload['payload'] ?? null;

        if (! is_string($token) || $token === '') {
            return;
        }

        try {
            // Note: In production, you should verify the JWT signature using Apple's Public Key
            // For brevity, we are looking at the decoded body
            $decoded = $this->decodeAppleToken($token);

            $claims = $decoded->claims();
            $eventJson = $claims->get('events');
            $event = json_decode((string) $eventJson, true);

            $appleId = (string) $claims->get('sub', '');
            $type = (string) ($event['type'] ?? '');

            if ($appleId === '') {
                return;
            }

            /** @var class-string<Model> $userModelClass */
            $userModelClass = config('auth.providers.users.model');
            /** @var Model|null $user */
            $user = null;

            if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
                $user = (new $userModelClass)->findByProvider('apple', $appleId);
            } else {
                // fallback for models not implementing the interface
                $user = $userModelClass::where('apple_id', $appleId)->first();
            }

            if (! $user) {
                return;
            }

            switch ($type) {
                case 'email-disabled':
                    // User disabled the email relay.
                    // You should probably flag the account as "email unreachable"
                    if (method_exists($user, 'setEmailVerifiedAt')) {
                        $user->setEmailVerifiedAt(null);
                    } elseif (isset($user->email_verified_at)) {
                        $user->email_verified_at = null;
                    } else {
                        $user->update(['email_verified_at' => null]);
                    }
                    break;

                case 'consent-revoked':
                    // User disconnected your app from their Apple ID
                    if ($user instanceof AuthenticatableRouterUser) {
                        $user->setProviderId('apple', '');
                    } elseif (isset($user->apple_id)) {
                        $user->apple_id = null;
                    } else {
                        $user->update(['apple_id' => null]);
                    }
                    break;
            }

            $user->save();

        } catch (\Exception $e) {
            Log::error('Apple Webhook Error: '.$e->getMessage());
        }
    }

    /**
     * Handles the Apple Socialite User callback.
     */
    public function handleAppleCallback(\Laravel\Socialite\Contracts\User $appleUser): ?Model
    {
        $appleId = (string) $appleUser->getId();
        $email = (string) $appleUser->getEmail();
        $name = (string) ($appleUser->getName() ?: 'Apple User');

        /** @var class-string<Model> $userModelClass */
        $userModelClass = config('auth.providers.users.model');
        /** @var Model|null $user */
        $user = null;

        // 1. Check for returning user by their unique Apple ID
        if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
            $user = (new $userModelClass)->findByProvider('apple', $appleId);
        } else {
            $user = $userModelClass::where('apple_id', $appleId)->first();
        }

        if ($user) {
            // Returning user found!
            return $user;
        }

        // 2. Check if a user already exists with this email address
        if ($email !== '') {
            if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
                $user = (new $userModelClass)->findByEmail($email);
            } else {
                $user = $userModelClass::where('email', $email)->first();
            }

            if ($user) {
                // Link their Apple ID to their existing account
                if ($user instanceof AuthenticatableRouterUser) {
                    $user->setProviderId('apple', $appleId);
                } else {
                    /** @phpstan-ignore-next-line */
                    $user->apple_id = $appleId;
                }
                $user->save();

                return $user;
            }
        }

        // 3. Brand New User - The "One-Shot" Moment!
        // We must save the name and email NOW, as we will never get them again.
        /** @var Model $user */
        $user = new $userModelClass;
        if ($user instanceof AuthenticatableRouterUser) {
            $user->setName($name);
            $user->setEmail($email);
            $user->setProviderId('apple', $appleId);
            // Apple guarantees the email belongs to the user, so we can mark it verified if possible
            if (method_exists($user, 'setEmailVerifiedAt')) {
                $user->setEmailVerifiedAt(now());
            }
        } else {
            /** @phpstan-ignore-next-line */
            $user->apple_id = $appleId;
            /** @phpstan-ignore-next-line */
            $user->email = $email;
            /** @phpstan-ignore-next-line */
            $user->name = $name;
            /** @phpstan-ignore-next-line */
            $user->email_verified_at = now();
        }
        $user->save();

        return $user;
    }

    /**
     * Decodes the Apple JWT token without verifying the signature (for now).
     *
     * @param  non-empty-string  $token
     */
    private function decodeAppleToken(string $token): UnencryptedToken
    {
        $parser = new Parser(new JoseEncoder);

        /** @var UnencryptedToken $parsed */
        $parsed = $parser->parse($token);

        return $parsed;
    }
}
