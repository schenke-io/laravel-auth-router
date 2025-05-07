## Installation

Install the package with composer:

```bash
  composer require schenke-io/laravel-auth-router
```

## Basic concept

This package is based on Socialite and is configured in a similar way. For each Login
you configure the keys in `config/services.php`.

In the routes/web.php file you add the Route helper `authRouter` to say which providers
you want to user and how 3 main routes are named in your application.

```php
Route::authRouter(/* provider/s */, $routeSuccess, $routeError, $routeHome, $canAddUsers);
```

| Parameter    | Definition                                                              | Examples                              |
|--------------|-------------------------------------------------------------------------|---------------------------------------|
| provider     | name of the social login providers, single string or array of strings   | 'google'  _or_ ['google','microsoft'] |
| routeSuccess | route after successful login                                            | 'dashboard'                           |
| routeError   | route after login failure, should be able to display errors as feedback | 'error'                               |
| routeHome    | route to a non protected view                                           | 'home'                                |  
| canAddUsers  | should unknown users be added or rejected                               | `true` or `false`                     |  

Route names can be same. When the homepage can display errors `routeError` and `routeHome` could be the same.

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

3) in the `routes/web.php` you define the route:

```php
// routes/web.php
Route::authRouter('google','dashboard','error','home',true);

``` 

## Advanced Example

If you want a selection of logins you basically just do:
1) fill the secret data into .env
2) register the services
3) add the route
```php
// routes/web.php
Route::authRouter(['google','paypal','microsoft'],'dashboard','error','home',true);

``` 

## Name conflicts

This line:
```php
// routes/web.php
Route::authRouter('google','dashboard','error','home',true);
``` 
registers the following routes:
- /login
- /login/google
- /callback/google
- /logout

and expects the 3 named routes to be defined: `dashboard`, `error` and `home`.

If this conflicts with extisng routes just prefix it with something:
```php
// routes/web.php
Route::prefix('auth')->name('auth.')->group(function () {
    Route::authRouter('google','dashboard','error','home',true);
});
``` 

Just use `php artisan route:list` to see which names and routes are automatically added.


