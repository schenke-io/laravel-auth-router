
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
