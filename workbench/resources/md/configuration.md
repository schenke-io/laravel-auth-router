## Configuration

There is no special configuration file; all setup is done via `config/services.php`.

### Standard Socialite Drivers

To make a standard Socialite driver stateless, add a `stateless` key in its `config/services.php` section:

```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'stateless' => true
],
```

### WorkOS Drivers

WorkOS drivers require an `api_key`, `client_id`, and `organization_id`:

```php
// config/services.php
'workos_google' => [
    'api_key' => env('WORKOS_API_KEY'),
    'client_id' => env('WORKOS_CLIENT_ID'),
    'organization_id' => env('WORKOS_ORGANIZATION_ID'),
],

'whatsapp' => [
    'api_key' => env('WHATSAPP_API_KEY'),
    'approved_emails' => env('WHATSAPP_APPROVED_EMAILS'),
]
```
