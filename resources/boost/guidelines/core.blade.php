<?php
/**
 * Core guidelines for the laravel-auth-router package.
 * Used as AI-agent context to ensure correct, idiomatic usage.
 */
?>
# Core Guidelines — `schenke-io/laravel-auth-router`

## Package Identity

- **Package:** `schenke-io/laravel-auth-router`
- **Namespace:** `SchenkeIo\LaravelAuthRouter`
- **Requires:** PHP ^8.2 (8.2–8.4), Laravel ^12.0
- **Purpose:** Register all social-auth routes (login, callback, logout) via a single fluent macro call. There is no separate package config file — all provider settings live in `config/services.php`.

---

## 1. The Macro — Only Entry Point

Always use the `Route::authRouter()` macro in `routes/web.php`. Never register auth routes manually.

```php
Route::authRouter(['google', 'microsoft'])
    ->success('dashboard')       // named route for successful login
    ->error('login')             // named route for auth failure
    ->home('home')               // named route for the home link in the UI
    ->canAddUsers(true)          // false = existing users only
    ->rememberMe(false)          // true = persist Auth session
    ->prefix('auth')             // URI prefix  →  /auth/login, /auth/callback/…
    ->name('auth.')              // route name prefix  →  auth.login, auth.callback.google
    ->middleware('throttle:60,1')
    ->emailConfirm($impl)        // optional EmailConfirmInterface for data-review flow
    ->showPayload(false)         // true = show user data page before finalising login
    ->canImpersonate('admin')    // optional gate name to enable impersonation
    ->defaultName('email-local') // optional fallback for missing user name
    ->register();                // REQUIRED — routes are not registered without this
```

**Invariants:**
- `->register()` must always terminate the chain. Omitting it silently skips route registration.
- `prefix()` controls URI segments; `name()` (or `prefix()` when `name()` is absent) controls route name segments.
- `->defaultName()` only accepts a `Closure` if you do not use route caching; use `'email-local'` or a string for cache safety.
- The `middleware` parameter is additive — `web` and `guest`/`auth` are always applied automatically.

---

## 2. Provider Keys

Valid provider keys are defined in `SchenkeIo\LaravelAuthRouter\Enums\Service`. Matching is case-insensitive and underscore-agnostic (e.g., `workos_google` resolves to `workos`).

| Key | Driver |
| :--- | :--- |
| `google` | Laravel Socialite |
| `facebook` | Laravel Socialite |
| `amazon` | Socialite community |
| `linkedin` | Socialite community |
| `paypal` | Socialite community |
| `stripe` | Socialite community |
| `microsoft` | Socialite community |
| `apple` | Custom (JWT via lcobucci/jwt) |
| `auth0` | Auth0 PHP SDK |
| `workos` | WorkOS PHP SDK |
| `logto` | Logto PHP SDK |
| `passkey` | OTP via email |
| `whatsapp` | Allowlist-based |
| `custom` | Socialite generic |

---

## 3. Provider Configuration (`config/services.php`)

Each provider reads `config('services.{provider}')`; the callback URL is injected automatically via named routes — **never** add `redirect` yourself. Standard Socialite providers need `client_id` + `client_secret` (+ optional `'stateless' => true`); WorkOS, Apple, Auth0, and Logto need extra keys.

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'stateless'     => true,   // eliminates state-mismatch errors in stateless apps
],
```

**Rules:** missing keys surface as "Setup Errors" on the login page, not exceptions; do not mix WorkOS with non-WorkOS providers in one call. For the full per-provider key reference, see the `auth-router-integration` skill (`providers.md`).

---

## 4. Routes Registered

The macro registers `login`, `logout`, and per-provider `login.{provider}` / `callback.{provider}` routes (plus `callback.payload` + `callback.finalize` when `showPayload` is on, and an Apple webhook for Apple). URIs use `prefix()`; names use `name()` (or `prefix()`). A single provider skips the selector UI and redirects straight to the OAuth flow. For the full route table, see the `auth-router-integration` skill (`integration.md`).

---

## 5. User Model Integration

The application's `User` model must implement `AuthenticatableRouterUser`:

```php
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Traits\InteractsWithAuthRouter; // default implementation

class User extends Authenticatable implements AuthenticatableRouterUser
{
    use InteractsWithAuthRouter;
}
```

Required interface methods (provided by the trait or implemented manually):

| Method | Purpose |
| :--- | :--- |
| `setName(string)` | Write the user's display name |
| `setEmail(string)` | Write the user's email |
| `setAvatar(string)` | Write the user's avatar URL |
| `findByEmail(string): ?Model` | Look up user by email |
| `findByProviderId(string): ?Model` | Look up user by provider ID |
| `setProviderId(string)` | Write the provider ID |
| `getProviderId(): ?string` | Read the provider ID |

**Convention:** The user is identified solely by their email address.

---

## 6. Error Handling

Two families: **setup errors** (developer mistakes) render in the "Setup Errors" panel on `/login` — fix `config/services.php`/`.env`, then `php artisan config:clear`. **Runtime errors** (OAuth / user-lifecycle) are logged (via `->logChannel()` if set, else `Log::error()` with an `[AuthRouter]` prefix) and redirected to `routeError` as a **structured context**.

Each runtime error carries a typed `Enums\Error` case, an `ErrorCategory`, a localised user-safe `info` message, a localised `recommendation()`, a masked/truncated technical `message`, and a unique 8-char reference code (`XXXX-XXXX`) shared between the log and the UI. All are stored under `Auth\SessionKey` constants and mirrored as `X-Custom-Error-Type` / `-Category` / `-Reference` headers for SPA/API clients. Read it in Blade via the `ErrorContext` DTO:

```blade
@use(SchenkeIo\LaravelAuthRouter\Auth\ErrorContext)
@php($error = ErrorContext::fromSession())

@if ($error)
    <p>{{ $error->info }}</p>
    <p>{{ $error->recommendation() }}</p>
    <small>{{ $error->type }} · Reference: {{ $error->reference }}</small>
@endif
```

For the full case→category table, session keys, and design rationale, see the `auth-router-integration` skill (`troubleshooting.md`).

---

## 7. Authentication Flow (sequence)

```
User → GET /auth/login/{provider}
     → Provider::login()  →  redirect to OAuth provider
     ← GET /auth/callback/{provider}?code=…
     → Provider::callback()
     → UserData::authAndRedirect()
           validate email
           if showPayload → store in session, show review page → POST finalize
           findByEmail() or create (if canAddUsers)
           Auth::guard('web')->login($user, $rememberMe)
           redirect to routeSuccess (or intended URL)
     ← on error → Enums\Error::redirect() → log + session + headers → routeError
```

---

## 8. Key Rules to Follow

1. **Always call `->register()`** at the end of the builder chain. Nothing registers without it.
2. **Never add `redirect` to `config/services.php`** — it is injected automatically per request.
3. **Implement `AuthenticatableRouterUser`** on the `User` model. The package will not find or create users without it.
4. **Do not mix WorkOS and non-WorkOS providers** in a single `authRouter()` call — it triggers `MixedProviders`.
5. **Use `'stateless' => true`** for providers that suffer OAuth state mismatches (common in single-page or API-hybrid apps).
6. **Read setup errors from the login page**, not from exception logs — configuration mistakes render there, not as 500 errors.
7. **Translation keys are namespaced** as `auth-router::errors.*` and `auth-router::login.*`. Override them via `lang/vendor/auth-router/`.
8. **Avoid Closures in `defaultName()` when using route caching**. Laravel cannot serialize Closures in cached routes. Use a string strategy like `'email-local'` instead.

---

## 9. Impersonation

`->canImpersonate($gate)` registers gate-protected `impersonate.start` / `impersonate.stop` routes. `start` logs in as the target user (storing the original ID in the session); `stop` reverts. While impersonating, further social logins are ignored to protect the session. See the `auth-router-integration` skill (`integration.md`) for routes and middleware.
