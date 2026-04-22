# Skill: `auth-router-integration`

| Field    | Value                             |
| :------- | :-------------------------------- |
| Category | project                           |
| Priority | high                              |

## Purpose

Register social authentication routes in a Laravel application using the `Route::authRouter()` macro. This replaces the manual boilerplate of defining login, callback, and logout routes for each provider individually.

## When to Use

- Adding social login or multi-provider authentication to a Laravel application for the first time.
- Customizing redirect behaviour for post-login success, authentication error, or home routes.
- Namespacing authentication routes (e.g., isolating an admin authentication flow under a prefix).

## Prerequisites

- `schenke-io/laravel-auth-router` is installed and the service provider is registered.
- At least one provider is configured in `config/services.php` (see skill `auth-router-provider-management`).

## Workflow

### Step 1 — Identify providers

Determine which providers the application will support. Valid keys are defined in `src/Auth/Service.php`. Common values:

```
google  microsoft  facebook  amazon  linkedin  paypal  stripe  auth0  apple
```

### Step 2 — Open the route file

Edit `routes/web.php` (or whichever route file applies for the intended middleware group).

### Step 3 — Call the macro

```php
use Illuminate\Support\Facades\Route;

Route::authRouter(['google', 'microsoft'])
    ->middleware(['web', 'throttle:60,1'])
    ->success('dashboard')       // redirect here on successful login
    ->error('login')             // redirect here on authentication failure
    ->canAddUsers(false)         // true = allow new user creation (default), false = existing users only
    ->prefix('auth')             // URL prefix, avoids collisions with application routes
    ->name('auth.')              // route name prefix
    ->register();                // always terminate the chain with register()
```

The `middleware()` method is an optional array or string of middleware to apply to the generated routes.

### Step 4 — Verify

Run `php artisan route:list --name=auth` and confirm that login, callback, and logout routes are present for each configured provider.

### Step 5 — Smoke-test the UI

Visit `/auth/login` (or your chosen prefix). The package renders a built-in provider-selector screen automatically when more than one provider is listed.

## Key Behaviours

| Scenario | Outcome |
| :--- | :--- |
| Single provider passed | No selector UI — user is redirected directly to the OAuth flow |
| Multiple providers | Built-in selector screen rendered automatically |
| `canAddUsers(false)` | Login is restricted to users already present in the database |
| Missing `->register()` | Routes are **not** registered — this is the most common setup mistake |

## Related Skills

- `auth-router-provider-management` — configure provider credentials before calling this skill.
- `auth-router-troubleshooting` — diagnose setup and runtime errors after registration.
