## Example Google login

1) In the `.env` file you have the credentials:

```dotenv
GOOGLE_CLIENT_ID=24242343242
GOOGLE_CLIENT_SECRET=3843430984
```

2) In `config/services.php` you define the service:

```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET')
]
```

3) In the `routes/web.php` you define the route:

```php
// routes/web.php
Route::authRouter('google')
    ->success('dashboard')
    ->error('error')
    ->home('home')
    ->register();
```

## Advanced Example

If you want a selection of logins you basically just do:
1) Fill the secret data into .env.
2) Register the services in `config/services.php`.
3) Add the routes:
```php
// routes/web.php
Route::authRouter(['google','paypal','microsoft'])
    ->success('dashboard')
    ->error('error')
    ->home('home')
    ->canAddUsers(true)
    ->register();
```

## Route Prefixing and Naming

To avoid route name conflicts or to group authentication routes under a specific path, you can use the `prefix()` and `name()` methods:

```php
// routes/web.php
Route::authRouter('google')
    ->prefix('auth')
    ->name('auth.')
    ->success('dashboard')
    ->register();
```

This generates routes like `/auth/login`, `/auth/callback/google`, and route names like `auth.login`, `auth.login.google`.
