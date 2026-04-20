# Skill: `auth-router-provider-management`

| Field    | Value                             |
| :------- | :-------------------------------- |
| Category | project                           |
| Priority | high                              |

## Purpose

Configure authentication provider credentials in `config/services.php` and `.env` so that `laravel-auth-router` can resolve and validate them at runtime.

## When to Use

- Adding a new social provider to an existing installation.
- Updating or rotating OAuth credentials for an existing provider.
- Configuring specialised providers that require non-standard keys (WorkOS, Apple, Auth0).

## Prerequisites

- `Route::authRouter()` is already set up, or is being set up in the same task.
- OAuth application credentials are available from the provider's developer console.

## Workflow

### Step 1 — Verify provider support

Check `src/Auth/Service.php` for the canonical list of accepted provider keys. Only keys present there will be recognised by the package.

### Step 2 — Add secrets to `.env`

Add the credentials as environment variables. Use the naming pattern `PROVIDER_CLIENT_ID` / `PROVIDER_CLIENT_SECRET`. Examples:

```dotenv
# Standard Socialite providers
GOOGLE_CLIENT_ID=…
GOOGLE_CLIENT_SECRET=…

MICROSOFT_CLIENT_ID=…
MICROSOFT_CLIENT_SECRET=…

# WorkOS
WORKOS_API_KEY=…
WORKOS_CLIENT_ID=…
WORKOS_ORGANIZATION_ID=…

# Apple
APPLE_TEAM_ID=…
APPLE_KEY_ID=…
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n…\n-----END PRIVATE KEY-----"

# Auth0
AUTH0_CLIENT_ID=…
AUTH0_CLIENT_SECRET=…
AUTH0_BASE_URL=https://your-tenant.auth0.com
```

### Step 3 — Register in `config/services.php`

Add an entry per provider. The exact keys depend on the provider type.

**Standard Socialite provider (Google, Facebook, Amazon, LinkedIn, Paypal, Stripe, Microsoft):**

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => '/auth/google/callback',
    'stateless'     => true,   // optional — skips session-based state verification
],
```

**WorkOS:**

```php
'workos' => [
    'api_key'         => env('WORKOS_API_KEY'),
    'client_id'       => env('WORKOS_CLIENT_ID'),
    'organization_id' => env('WORKOS_ORGANIZATION_ID'),
],
```

**Apple:**

```php
'apple' => [
    'team_id'     => env('APPLE_TEAM_ID'),
    'key_id'      => env('APPLE_KEY_ID'),
    'private_key' => env('APPLE_PRIVATE_KEY'),
    'redirect'    => '/auth/apple/callback',
],
```

**Auth0:**

```php
'auth0' => [
    'client_id'     => env('AUTH0_CLIENT_ID'),
    'client_secret' => env('AUTH0_CLIENT_SECRET'),
    'base_url'      => env('AUTH0_BASE_URL'),
    'redirect'      => '/auth/auth0/callback',
],
```

### Step 4 — Verify

Visit the `/login` route (or your configured prefix). Any missing or misconfigured keys appear immediately in the **"Setup Errors"** section on the provider-selector screen — no application restart is needed.

## Provider Reference

| Provider   | Required keys                                    | SDK / Driver         |
| :--------- | :----------------------------------------------- | :------------------- |
| `google`   | `client_id`, `client_secret`                     | Socialite            |
| `microsoft`| `client_id`, `client_secret`                     | Socialite            |
| `facebook` | `client_id`, `client_secret`                     | Socialite            |
| `amazon`   | `client_id`, `client_secret`                     | Socialite            |
| `linkedin` | `client_id`, `client_secret`                     | Socialite            |
| `paypal`   | `client_id`, `client_secret`                     | Socialite            |
| `stripe`   | `client_id`, `client_secret`                     | Socialite            |
| `auth0`    | `client_id`, `client_secret`, `base_url`         | Auth0 SDK            |
| `apple`    | `team_id`, `key_id`, `private_key`               | Custom service       |
| `workos`   | `api_key`, `client_id`, `organization_id`        | WorkOS SDK           |

## Related Skills

- `auth-router-integration` — register routes after providers are configured.
- `auth-router-troubleshooting` — resolve errors reported on the setup screen.
