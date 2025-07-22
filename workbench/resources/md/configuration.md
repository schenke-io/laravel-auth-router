## Configuration

There is no special configuration file, all setup 
is done over `config/services.php`.

To make a standard Socialite driver stateless, add 
a `stateless` key in 
its `config/services.php` section:


```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'stateless' => true
]

``` 
