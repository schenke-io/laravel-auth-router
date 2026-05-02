<?php

namespace SchenkeIo\LaravelAuthRouter\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User;
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
    public function handleServerNotification(array $payload, bool $useProviderId = false): void
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
            $allClaims = $claims->all();
            $eventJson = $allClaims['events'] ?? '';
            $event = json_decode((string) $eventJson, true);

            $email = (string) ($allClaims['email'] ?? '');
            $sub = (string) ($allClaims['sub'] ?? '');
            $type = (string) ($event['type'] ?? '');
            if ($email === '' && $sub === '') {
                return;
            }

            /** @var class-string<Model> $userModelClass */
            $userModelClass = config('auth.providers.users.model');
            /** @var Model|null $user */
            $user = null;

            if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
                $userFactory = new $userModelClass;
                if ($useProviderId && $sub) {
                    $user = $userFactory->findByProviderId($sub);
                }
                if (! $user && $email) {
                    $user = $userFactory->findByEmail($email);
                }
            } else {
                // fallback for models not implementing the interface
                if ($useProviderId && $sub) {
                    $user = $userModelClass::where('provider_id', $sub)->first();
                }
                if (! $user && $email) {
                    $user = $userModelClass::where('email', $email)->first();
                }
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
    public function handleAppleCallback(User $appleUser, bool $useProviderId = false): ?Model
    {
        $email = (string) $appleUser->getEmail();
        $sub = (string) $appleUser->getId();
        $name = (string) ($appleUser->getName() ?: 'Apple User');

        if ($email === '' && $sub === '') {
            return null;
        }

        /** @var class-string<Model> $userModelClass */
        $userModelClass = config('auth.providers.users.model');
        /** @var Model|null $user */
        $user = null;

        // check if a user already exists
        if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
            $userFactory = new $userModelClass;
            if ($useProviderId && $sub) {
                $user = $userFactory->findByProviderId($sub);
            }
            if (! $user && $email) {
                $user = $userFactory->findByEmail($email);
            }
        } else {
            if ($useProviderId && $sub) {
                $user = $userModelClass::where('provider_id', $sub)->first();
            }
            if (! $user && $email) {
                $user = $userModelClass::where('email', $email)->first();
            }
        }

        if ($user) {
            return $user;
        }

        // Brand New User - The "One-Shot" Moment!
        // We must save the name and email NOW, as we will never get them again.
        /** @var Model $user */
        $user = new $userModelClass;
        if ($user instanceof AuthenticatableRouterUser) {
            $user->setName($name);
            $user->setEmail($email);
            if ($sub) {
                $user->setProviderId($sub);
            }
            // Apple guarantees the email belongs to the user, so we can mark it verified if possible
            if (method_exists($user, 'setEmailVerifiedAt')) {
                $user->setEmailVerifiedAt(now());
            }
        } else {
            /** @phpstan-ignore-next-line */
            $user->email = $email;
            /** @phpstan-ignore-next-line */
            $user->name = $name;
            if ($sub) {
                /** @phpstan-ignore-next-line */
                $user->provider_id = $sub;
            }
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
