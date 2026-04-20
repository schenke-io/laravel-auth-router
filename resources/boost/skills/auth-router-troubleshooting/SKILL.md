# Skill: `auth-router-troubleshooting`

| Field    | Value                              |
| :------- | :--------------------------------- |
| Category | project                            |
| Priority | medium                             |

## Purpose

Diagnose and resolve errors that appear during setup or at runtime in the `laravel-auth-router` authentication flow.

## When to Use

- **Setup errors** appear on the `/login` provider-selector screen.
- A user reaches an error page after attempting to authenticate.
- An OAuth state mismatch or missing email prevents login from completing.
- The application needs to present localised error messages to end users.

## Error Categories

The package distinguishes two distinct error categories with separate resolution paths.

### Category A — Setup Errors (developer mistakes)

These are detected before the OAuth flow begins and displayed directly on the login-selector screen.

**Symptoms:** Red "Setup Errors" section visible on the `/login` page.

**Common causes and fixes:**

| Error | Cause | Fix |
| :--- | :--- | :--- |
| Missing `client_id` | Key absent from `config/services.php` | Add the key; see skill `auth-router-provider-management` |
| Missing `client_secret` | Key absent or `.env` variable not set | Set the `.env` variable and re-cache config |
| Unknown provider key | Typo in the provider array passed to `authRouter()` | Check valid keys in `src/Auth/Service.php` |
| Missing `->register()` | Route macro chain not terminated | Append `->register()` to the chain in `routes/web.php` |

**Resolution flow:**

1. Read the error label shown on the login page.
2. Cross-reference with `config/services.php` and the `.env` file.
3. Apply the fix from the table above.
4. Run `php artisan config:clear` if values were changed in `.env`.
5. Reload the login page — errors disappear immediately once resolved.

---

### Category B — Runtime Errors (OAuth / user-lifecycle failures)

These occur during or after the provider's OAuth callback.

**Symptoms:** User is redirected to the `->error()` route; no obvious UI error during the initial redirect.

**Step 1 — Read the session error data**

The package stores structured error information in the session under two keys:

```php
// Localised message safe to display to the end user:
session('authRouterErrorInfo');

// Machine-readable error type for programmatic handling:
// Delivered via the X-Custom-Error-Type response header.
```

**Step 2 — Map the error type**

| `X-Custom-Error-Type` value | Meaning | Typical fix |
| :--- | :--- | :--- |
| `InvalidEmail` | Provider returned no email or an invalid address | Enable the `email` scope for the provider in `config/services.php` |
| `MixedProviders` | User's email is already registered under a different provider | Show a message directing the user to their original login method |
| `UserNotFound` | `canAddUsers(false)` is set and the user does not exist | Either create the user manually or enable `canAddUsers(true)` |
| `OAuthStateMismatch` | Session expired or cookies blocked; Socialite state check failed | Set `'stateless' => true` in `config/services.php` for the provider |
| `ProviderError` | The OAuth provider returned an explicit error response | Log `session('authRouterErrorInfo')` and check the provider's dashboard |

**Step 3 — Surface the error to the user**

In the Blade view associated with the `->error()` route, display the localised session message:

```blade
@if (session('authRouterErrorInfo'))
    <div class="alert alert-danger" role="alert">
        {{ session('authRouterErrorInfo') }}
    </div>
@endif
```

**Step 4 — Programmatic branching (optional)**

If the application must respond differently per error type, inspect the header value that the package sets on the redirect response. You can read this from a custom middleware or from the session-stored copy:

```php
// In a controller or middleware:
$errorType = $request->header('X-Custom-Error-Type');

match ($errorType) {
    'MixedProviders' => redirect()->route('login')->with('info', __('auth.mixed_providers')),
    'UserNotFound'   => abort(403, 'Account not found.'),
    default          => redirect()->route('login'),
};
```

## Debugging Checklist

```
[ ] Visited /login — no "Setup Errors" shown
[ ] config/services.php has correct keys for all providers
[ ] .env variables are set and config cache is cleared
[ ] ->register() is at the end of the authRouter() chain
[ ] Callback URL registered in the provider's OAuth app matches the route exactly
[ ] 'stateless' => true set for providers that suffer state mismatch errors
[ ] Blade error view reads session('authRouterErrorInfo')
[ ] Error redirects tested manually via an intentionally broken OAuth flow
```

## Related Skills

- `auth-router-provider-management` — fix missing or incorrect provider configuration.
- `auth-router-integration` — fix route registration issues.
