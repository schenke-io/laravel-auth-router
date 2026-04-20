## Installation

Install the package with composer:

```bash
composer require schenke-io/laravel-auth-router
```

## Basic concept

In the `routes/web.php` file you use the `Route::authRouter()` macro to define which providers you want to use and your registration policy. This package handles the configuration through `config/services.php`.

```php
Route::authRouter(['google', 'microsoft'])
    ->success('dashboard')
    ->error('login')
    ->home('home')
    ->canAddUsers(true)
    ->rememberMe(false)
    ->prefix('auth')
    ->name('auth.')
    ->register();
```

| Method           | Definition                                                              | Examples                              |
|------------------|-------------------------------------------------------------------------|---------------------------------------|
| `success()`      | route after successful login                                            | 'dashboard'                           |
| `error()`        | route after login failure, should be able to display errors as feedback | 'error'                               |
| `home()`         | route to a non protected view (default: 'home')                         | 'home'                                |
| `canAddUsers()`  | should unknown users be added or rejected (default: true)               | `true` or `false`                     |
| `rememberMe()`   | stores the login even when session expires (default: false)             | `true` or `false`                     |
| `prefix()`       | prefix for the URIs                                                     | 'auth'                                |
| `name()`         | prefix for the route names                                              | 'auth.'                               |
| `middleware()`   | additional middleware for the routes                                    | 'web' or `['web', 'throttle']`        |
| `emailConfirm()` | implementation of `EmailConfirmInterface` to handle email verification  | `$myEmailConfirm`                     |
| `register()`     | **Mandatory** call to actually register the routes                      |                                       |

Route names can be same. If the homepage can display errors `error()` and `home()` could be the same.
When the service configuration is not complete not all routes will be created.

## Login and Logout flow

In the app just link to the `login` route (or `auth.login` if using `.name('auth.')`).
It either displays the selector page, configuration errors or redirect to a single login provider.

For logout just do an empty POST to the `logout` route. Only authenticated users can use the logout.

## Name conflicts

If you have multiple authentication setups or want to avoid name conflicts, use `prefix()` and `name()`:

```php
// routes/web.php
Route::authRouter('google')
    ->prefix('admin')
    ->name('admin.')
    ->success('admin.dashboard')
    ->register();
```
Registers the following routes when the configuration is free of errors:
- /admin/login (named `admin.login`)
- /admin/login/google (named `admin.login.google`)
- /admin/callback/google (named `admin.callback.google`)
- /admin/logout (named `admin.logout`)

Just use `php artisan route:list` to see which names and routes have been added.


