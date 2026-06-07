# Reference: Provider Management

Configure authentication provider credentials in `config/services.php` and `.env` so the package can resolve and validate them at runtime. There is **no separate package config file** — every provider reads `config('services.{provider}')`.

## When to Use

- Adding a new social provider to an existing installation.
- Rotating OAuth credentials for an existing provider.
- Configuring specialised providers with non-standard keys (WorkOS, Apple, Auth0, Logto).

## Workflow

### Step 1 — Verify provider support

Only keys present in `SchenkeIo\LaravelAuthRouter\Enums\Service` are recognised. Matching is case-insensitive and underscore-agnostic (`workos_google` → `workos`).

### Step 2 — Add secrets to `.env`

```dotenv
GOOGLE_CLIENT_ID=…
GOOGLE_CLIENT_SECRET=…

MICROSOFT_CLIENT_ID=…
MICROSOFT_CLIENT_SECRET=…

WORKOS_API_KEY=…
WORKOS_CLIENT_ID=…
WORKOS_CLIENT_SECRET=…

APPLE_CLIENT_ID=…
APPLE_TEAM_ID=…
APPLE_KEY_ID=…
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n…\n-----END PRIVATE KEY-----"

AUTH0_CLIENT_ID=…
AUTH0_CLIENT_SECRET=…
AUTH0_DOMAIN=https://your-tenant.auth0.com
AUTH0_COOKIE_SECRET=…
```

### Step 3 — Register in `config/services.php`

**Standard Socialite provider** (Google, Facebook, Amazon, LinkedIn, Paypal, Stripe, Microsoft, custom):

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'stateless'     => true,   // optional — skips session-based state verification
],
```

**WorkOS:**

```php
'workos' => [
    'client_id'     => env('WORKOS_CLIENT_ID'),
    'api_key'       => env('WORKOS_API_KEY'),
    'client_secret' => env('WORKOS_CLIENT_SECRET'),
],
```

**Apple:**

```php
'apple' => [
    'client_id'   => env('APPLE_CLIENT_ID'),
    'team_id'     => env('APPLE_TEAM_ID'),
    'key_id'      => env('APPLE_KEY_ID'),
    'private_key' => env('APPLE_PRIVATE_KEY'),
],
```

**Auth0:**

```php
'auth0' => [
    'client_id'     => env('AUTH0_CLIENT_ID'),
    'client_secret' => env('AUTH0_CLIENT_SECRET'),
    'domain'        => env('AUTH0_DOMAIN'),
    'cookie_secret' => env('AUTH0_COOKIE_SECRET'),
],
```

### Step 4 — Verify

Visit `/login` (or your prefix). Missing or misconfigured keys appear immediately in the **"Setup Errors"** section on the selector screen — no restart needed. If you changed `.env`, run `php artisan config:clear` first.

## Rules

- **Never add `redirect`** to a provider entry — the callback URL is injected automatically per request from the named route.
- **`'stateless' => true`** avoids OAuth state-mismatch errors in stateless/SPA-hybrid apps.
- **Do not mix WorkOS providers with non-WorkOS providers** in the same `authRouter()` call — it raises `MixedProviders`.
- Missing config keys surface as **setup errors** on the login page, not as exceptions.

## Provider Reference

| Provider    | Required keys                                        | SDK / Driver         |
| :---------- | :--------------------------------------------------- | :------------------- |
| `google`    | `client_id`, `client_secret`                         | Socialite            |
| `microsoft` | `client_id`, `client_secret`                         | Socialite community  |
| `facebook`  | `client_id`, `client_secret`                         | Socialite            |
| `amazon`    | `client_id`, `client_secret`                         | Socialite community  |
| `linkedin`  | `client_id`, `client_secret`                         | Socialite community  |
| `paypal`    | `client_id`, `client_secret`                         | Socialite community  |
| `stripe`    | `client_id`, `client_secret`                         | Socialite community  |
| `auth0`     | `client_id`, `client_secret`, `domain`, `cookie_secret` | Auth0 PHP SDK     |
| `apple`     | `client_id`, `team_id`, `key_id`, `private_key`      | Custom (JWT)         |
| `workos`    | `client_id`, `api_key`, `client_secret`              | WorkOS PHP SDK       |
| `logto`     | per Logto SDK                                        | Logto PHP SDK        |
| `passkey`   | —                                                    | OTP via email        |
| `whatsapp`  | allowlist                                            | Allowlist-based      |
| `custom`    | `client_id`, `client_secret`                         | Socialite generic    |

## Related

- [`integration.md`](integration.md) — register routes after providers are configured.
- [`troubleshooting.md`](troubleshooting.md) — resolve errors reported on the setup screen.
