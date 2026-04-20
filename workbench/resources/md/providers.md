# Providers

The following providers are supported:

- `amazon`
- `auth0`
- `facebook`
- `google`
- `linkedin`
- `microsoft`
- `paypal`
- `stripe`
- `apple`
- `whatsapp`
- `custom`
- `workos_apple`
- `workos_email`
- `workos_google`
- `workos_linkedin`
 
## WorkOS Configuration
 
To use WorkOS providers, you must set the following environment variables:
 
- `WORKOS_API_KEY` (stored in `services.workos.api_key`)
- `WORKOS_CLIENT_ID` (stored in `services.workos.client_id`)
- `WORKOS_ORGANIZATION_ID` (stored in `services.workos.organization_id`)

## WhatsApp Configuration

To use WhatsApp login, you must set the following environment variables:

- `WHATSAPP_API_KEY` (stored in `services.whatsapp.api_key`)
- `WHATSAPP_APPROVED_EMAILS` (stored in `services.whatsapp.approved_emails`) - a comma-separated list of approved emails.

WhatsApp login requires an approved email to be provided first. The flow includes a button to start the login and a waiting page for the user to confirm via their WhatsApp device.

### Email and Password Flow
The `workos_email` provider supports both magic link flows (GET) and direct email/password authentication (POST). The login view includes fields for both, allowing users to choose their preferred method.

## Apple Configuration

To use Apple Sign-In, you must set the following environment variables:

- `APPLE_CLIENT_ID` (your Service ID)
- `APPLE_TEAM_ID` (your Apple Team ID)
- `APPLE_KEY_ID` (your Apple Key ID)
- `APPLE_PRIVATE_KEY` (the content of your `.p8` key file)

The package automatically handles dynamic client secret generation required by Apple.

### Server-to-Server Notifications

Apple can send notifications when a user disables email relay or revokes consent. This package provides a webhook handler for these events.

**Webhook URL:** `https://your-app.com/auth/apple/webhook` (assuming default prefix)

**Configuration:**
1. In the Apple Developer portal, set the "Server-to-Server Notification Endpoint" to the URL above.
2. Ensure the route is excluded from CSRF protection in your application.

In Laravel 11+, you can do this in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'auth/apple/webhook',
    ]);
})
```

The handler will:
- Mark `email_verified_at` as `null` if the user disables email relay.

### Apple Socialite Callback

Apple Sign-In is unique because it only provides the user's name and email on the **first** successful authentication ("One-Shot"). Subsequent logins only provide the unique Apple ID (`sub` claim).

This package handles this by:
1.  **Returning User:** Checking for an existing user by their email.
2.  **New User (One-Shot):** Creating a new user and capturing the name and email provided by Apple on their first login.

## Custom Provider

The `custom` provider is a flexible authentication option that can be fully configured via your `config/services.php` and `.env` file. It leverages Laravel Socialite to provide a "free programmable" login flow.

### Real vs. Fake Usage

In your `.env` file, you can define the service keys for the `custom` provider. 

**Real Provider:**
To connect to a real OAuth service, provide the actual client credentials:
```env
CUSTOM_CLIENT_ID=your-real-client-id
CUSTOM_CLIENT_SECRET=your-real-client-secret
CUSTOM_REDIRECT_URI=https://your-app.com/auth/custom/callback
```

**Fake Provider (Mocking):**
For local development or CI, you can use a mock driver or a "fake" configuration. Since the `custom` provider uses standard Socialite logic, you can easily mock it in your tests:
```php
Socialite::fake();
```
This allows you to test the entire login flow without ever making a real network request.

## Restriction: Mixing Providers
 
A collection of login providers passed to `Route::authRouter` must **NOT** contain a mix of WorkOS and non-WorkOS providers. If any WorkOS provider is present, all other providers in that same set must also be WorkOS providers.
