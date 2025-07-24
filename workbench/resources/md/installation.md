## Installation

Install the package with composer:

```bash
  composer require schenke-io/laravel-auth-router
```

## Basic concept

This package includes services and has to be configured in `config/services.php` only.

In the `routes/web.php` file you add the Route helper `authRouter` to define which providers
you want to use and your registration policy.

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
| remeberMe    | stores the login even when session expirers                             | `true` or `false`                     |  

Route names can be same. If the homepage can display errors `routeError` and `routeHome` could be the same.
When the service configuration is not complete not all routes will be created.

## Login and Logout flow

In the app just  link to the `login` route.
It either displays the  selector page, configuration errors or redirect to a single login provider.

For logout just do an empty POST to the `logout` route. Only authenticated users can use the logout.

## Name conflicts

This line:
```php
// routes/web.php
Route::authRouter('google','dashboard','error','home',true);
``` 
registers the following routes when the configuration is free of errors:
- /login
- /login/google
- /callback/google
- /logout

and expects the 3 named routes to be defined: `dashboard`, `error` and `home`.

If this conflicts with other existing routes just prefix it with something:
```php
// routes/web.php
Route::prefix('any')->name('any.')->group(function () {
    Route::authRouter('google','dashboard','error','home',true);
});
``` 

Just use `php artisan route:list` to see which names and routes have been added.


