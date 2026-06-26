# Reference: Route Integration

Register social authentication routes with the `Route::authRouter()` macro. This replaces the manual boilerplate of defining login, callback, and logout routes per provider.

## Prerequisites

- `schenke-io/laravel-auth-router` is installed and its service provider is registered.
- At least one provider is configured in `config/services.php` (see [`providers.md`](providers.md)).
- The `User` model implements `AuthenticatableRouterUser` (use the `InteractsWithAuthRouter` trait).

## User Model Integration

### Nameless logins

Identity providers may legitimately return no display name (email-only Logto/WorkOS accounts, some Apple relays, passkey, WhatsApp). The package never invents a name — on user creation the name is left unset. Ensure your `users.name` column is **nullable** or carries a **database default**. If you keep Laravel's default `NOT NULL` without a fallback, new nameless sign-ups will fail with a database constraint error.

## Workflow

### Step 1 — Identify providers

Valid keys are defined in `SchenkeIo\LaravelAuthRouter\Enums\Service`. Common values:

```
google  microsoft  facebook  amazon  linkedin  paypal  stripe  auth0  apple  workos  logto  passkey  whatsapp  custom
```

### Step 2 — Call the macro in `routes/web.php`

```php
use Illuminate\Support\Facades\Route;

Route::authRouter(['google', 'microsoft'])
    ->middleware(['throttle:60,1']) // additive — web + guest/auth are always applied
    ->success('dashboard')          // redirect on successful login
    ->error('login')                // redirect on failure (view reads ErrorContext)
    ->home('home')                  // named route for the UI home link
    ->canAddUsers(false)            // true (default) = create new users; false = existing only
    ->defaultName('email-local')    // optional fallback: 'email-local' or a Closure
    ->rememberMe(false)             // true = persist the auth session
    ->prefix('auth')                // URI prefix → /auth/login, /auth/callback/…
    ->name('auth.')                 // route-name prefix → auth.login, auth.callback.google
    ->emailConfirm($impl)           // optional EmailConfirmInterface data-review flow
    ->showPayload(false)            // true = show user-data page before finalising login
    ->logChannel('auth')            // optional log channel for success/error/debug entries
    ->canImpersonate('admin')       // optional gate name to enable impersonation
    ->register();                   // REQUIRED — always terminate the chain
```

`prefix()` controls URI segments; `name()` (or `prefix()` when `name()` is absent) controls route-name segments.

**Note:** If you use route caching (`php artisan route:cache`), avoid using a `Closure` in `->defaultName()`. Use a string strategy like `'email-local'` instead.

### Step 3 — Verify

Run `php artisan route:list --name=auth` and confirm login, callback, and logout routes exist for each provider. If impersonation is enabled, also confirm `impersonate.start` and `impersonate.stop`.

### Step 4 — Smoke-test the UI

Visit `/auth/login` (or your prefix). A built-in provider-selector screen renders automatically when more than one provider is listed.

## Routes Registered

For `->prefix('auth')` with `['google', 'microsoft']`:

| Method | URI | Route Name | Middleware |
| :--- | :--- | :--- | :--- |
| GET\|POST | `/auth/login` | `auth.login` | web, guest |
| POST | `/auth/logout` | `auth.logout` | web, auth |
| GET\|POST | `/auth/login/google` | `auth.login.google` | web, guest |
| GET | `/auth/callback/google` | `auth.callback.google` | web, guest |
| GET\|POST | `/auth/login/microsoft` | `auth.login.microsoft` | web, guest |
| GET | `/auth/callback/microsoft` | `auth.callback.microsoft` | web, guest |
| GET | `/auth/callback/payload` | `auth.callback.payload` | web, guest (if `showPayload`) |
| POST | `/auth/callback/finalize` | `auth.callback.finalize` | web, guest (if `showPayload`) |
| POST | `/auth/apple/webhook` | — | (Apple only) |
| GET | `/auth/impersonate/start/{user}` | `auth.impersonate.start` | web, auth, can:admin |
| POST | `/auth/impersonate/stop` | `auth.impersonate.stop` | web, auth |

## Key Behaviours

| Scenario | Outcome |
| :--- | :--- |
| Single provider passed | No selector UI — redirected directly to the OAuth flow |
| Multiple providers | Built-in selector screen rendered automatically |
| `canAddUsers(false)` | Login restricted to users already in the database |
| `canImpersonate($gate)` | Registers `/impersonate/start/{user}` and `/impersonate/stop` routes |
| Missing `->register()` | Routes are **not** registered — the most common setup mistake |

## Impersonation

```php
Route::authRouter(['google'])
    ->canImpersonate('admin-gate')
    ->register();
```

| Method | URI | Route Name | Middleware |
| :--- | :--- | :--- | :--- |
| GET | `/impersonate/start/{user}` | `impersonate.start` | web, auth, can:admin-gate |
| POST | `/impersonate/stop` | `impersonate.stop` | web, auth |

- `start` stores the current user ID in the session and logs in as the target user.
- `stop` reverts to the original user and clears the impersonation session.
- While impersonating, further social logins are ignored to protect the session.

## Related

- [`providers.md`](providers.md) — configure credentials before registering.
- [`troubleshooting.md`](troubleshooting.md) — diagnose setup and runtime errors.
