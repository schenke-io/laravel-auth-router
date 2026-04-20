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
- **Requires:** PHP ^8.3, Laravel ^12.0
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
    ->register();                // REQUIRED — routes are not registered without this
```

**Invariants:**
- `->register()` must always terminate the chain. Omitting it silently skips route registration.
- `prefix()` controls URI segments; `name()` (or `prefix()` when `name()` is absent) controls route name segments.
- The `middleware` parameter is additive — `web` and `guest`/`auth` are always applied automatically.

---

## 2. Provider Keys

Valid provider keys are defined in `SchenkeIo\LaravelAuthRouter\Auth\Service`. Matching is case-insensitive and underscore-agnostic (e.g., `workos_google` resolves to `workos`).

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
| `passkey` | OTP via email |
| `whatsapp` | Allowlist-based |
| `custom` | Socialite generic |

---

## 3. Provider Configuration (`config/services.php`)

Each provider reads its config from `config('services.{provider_name}')`. The `redirect` key is set **automatically** — never hardcode it.

```php
// Standard Socialite provider (google, facebook, amazon, linkedin, paypal, stripe, custom)
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'stateless'     => true,   // eliminates state-mismatch errors in stateless apps
],

// Microsoft (socialite community driver)
'microsoft' => [
    'client_id'     => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'stateless'     => true,
],

// Apple
'apple' => [
    'client_id'   => env('APPLE_CLIENT_ID'),
    'team_id'     => env('APPLE_TEAM_ID'),
    'key_id'      => env('APPLE_KEY_ID'),
    'private_key' => env('APPLE_PRIVATE_KEY'),
],

// Auth0
'auth0' => [
    'client_id'     => env('AUTH0_CLIENT_ID'),
    'client_secret' => env('AUTH0_CLIENT_SECRET'),
    'domain'        => env('AUTH0_DOMAIN'),
    'cookie_secret' => env('AUTH0_COOKIE_SECRET'),
],

// WorkOS
'workos' => [
    'client_id'   => env('WORKOS_CLIENT_ID'),
    'api_key'     => env('WORKOS_API_KEY'),
    'client_secret' => env('WORKOS_CLIENT_SECRET'),
],
```

**Rules:**
- Never add a `redirect` key manually — it is injected by `BaseProvider::registerRoutes()`.
- Missing keys surface as "Setup Errors" on the login page, not as exceptions.
- Do not mix WorkOS providers with non-WorkOS providers in the same `authRouter()` call.

---

## 4. Routes Registered

For a call with `->prefix('auth')` and providers `['google', 'microsoft']`:

| Method | URI | Route Name | Middleware |
| :--- | :--- | :--- | :--- |
| GET\|POST | `/auth/login` | `auth.login` | web, guest |
| POST | `/auth/logout` | `auth.logout` | web, auth |
| GET\|POST | `/auth/login/google` | `auth.login.google` | web, guest |
| GET | `/auth/callback/google` | `auth.callback.google` | web, guest |
| GET\|POST | `/auth/login/microsoft` | `auth.login.microsoft` | web, guest |
| GET | `/auth/callback/microsoft` | `auth.callback.microsoft` | web, guest |
| GET | `/auth/callback/payload` | `auth.callback.payload` | web, guest (if showPayload) |
| POST | `/auth/callback/finalize` | `auth.callback.finalize` | web, guest (if showPayload) |
| POST | `/auth/apple/webhook` | — | (Apple only) |

When a single provider is passed, the login page redirects directly to that provider — no selector UI is shown.

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
| `findByProvider(string $provider, string $id): ?Model` | Look up user by provider + provider user ID |
| `setProviderId(string $provider, string $id, ?string $field)` | Link a provider ID to the user row |

**Convention:** The provider ID column is `{provider_name}_id` (e.g., `google_id`, `apple_id`). To enable storage of this ID, you must set `'user_id_field' => true` in `config/services.{provider_name}`.

---

## 6. Error Handling

### Setup Errors (developer mistakes)
Detected before any OAuth flow. Displayed in the "Setup Errors" panel on the `/login` page. Fix by correcting `config/services.php` or `.env`, then run `php artisan config:clear`.

### Runtime Errors (OAuth / user-lifecycle)

All runtime errors redirect to `routeError` and carry:

| Mechanism | Key / Name | Content |
| :--- | :--- | :--- |
| Session | `authRouterErrorInfo` | Localised string safe to display to the user |
| Session | `authRouterErrorMessage` | Raw technical message for logging |
| Response header | `X-Custom-Error-Type` | `Error` enum case name |

**Error case names (`X-Custom-Error-Type`):**

| Name | Meaning |
| :--- | :--- |
| `UnknownService` | Provider key not in `Service` enum |
| `ServiceNotSet` | No config array for provider in `services.php` |
| `ConfigNotSet` | A required config key is empty |
| `UnableToAddNewUsers` | `canAddUsers=false` and user does not exist |
| `EmailMissing` | Provider returned no email |
| `InvalidEmail` | Email fails PHP `FILTER_VALIDATE_EMAIL` |
| `LocalAuth` | Socialite driver failed locally |
| `RemoteAuth` | Provider returned an error response |
| `State` | OAuth state parameter mismatch |
| `Network` | Connection error to provider |
| `InvalidRequest` | Malformed request |
| `MixedProviders` | WorkOS mixed with non-WorkOS in same call |
| `InvalidCredentials` | Credentials rejected |

**Displaying errors in Blade:**

```blade
@if (session('authRouterErrorInfo'))
    <div class="alert alert-danger">{{ session('authRouterErrorInfo') }}</div>
@endif
```

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
           findByProvider() or findByEmail() or create (if canAddUsers)
           setProviderId()
           Auth::guard('web')->login($user, $rememberMe)
           redirect to routeSuccess (or intended URL)
     ← on error → Error::redirect() → session + header → routeError
```

---

## 8. Key Rules to Follow

1. **Always call `->register()`** at the end of the builder chain. Nothing registers without it.
2. **Never add `redirect` to `config/services.php`** — it is injected automatically per request.
3. **Implement `AuthenticatableRouterUser`** on the `User` model. The package will not find or create users without it.
4. **Do not mix WorkOS and non-WorkOS providers** in a single `authRouter()` call — it triggers `MixedProviders`.
5. **Use `'stateless' => true`** for providers that suffer OAuth state mismatches (common in single-page or API-hybrid apps).
6. **Read setup errors from the login page**, not from exception logs — configuration mistakes render there, not as 500 errors.
7. **Add provider ID columns to the `users` table** (`google_id`, `apple_id`, etc.) and set `'user_id_field' => true` in `config/services.php` for each provider to enable linking.
8. **Translation keys are namespaced** as `auth-router::errors.*` and `auth-router::login.*`. Override them via `lang/vendor/auth-router/`.
