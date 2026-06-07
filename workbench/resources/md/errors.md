## Errors

The package handles two types of errors differently:

1) setup errors the developer can handle
2) runtime errors which influence the user experience

### Setup errors

The setup errors are shown in the `/login` selector page. 
If you have any errors with your setup, you can fix them in `config/services.php`.
Its mainly missing keys, or missing provider names in `config/services.php`.

### Runtime errors

Runtime errors are stored in the session and can be handled by your application.
Whenever a runtime error occurs, it is automatically logged. If no specific `logChannel` is configured in the `authRouter()` chain, it defaults to the standard `error` log with an `[AuthRouter]` prefix.

#### Session Keys and Headers

All session keys are defined as constants in the `SchenkeIo\LaravelAuthRouter\Auth\SessionKey` class.

| Constant           | Session Key                   | Header                      | Description                               |
|--------------------|-------------------------------|-----------------------------|-------------------------------------------|
| `ERROR_INFO`       | `auth-router-error-info`      |                             | Localized user-friendly error message.    |
| `ERROR_MESSAGE`    | `auth-router-error-message`   |                             | Technical error message (truncated).      |
| `ERROR_TYPE`       | `auth-router-error-type`      | `X-Custom-Error-Type`       | The name of the error case (e.g. `State`).|
| `ERROR_CATEGORY`   | `auth-router-error-category`  | `X-Custom-Error-Category`   | The category of the error.                |
| `ERROR_REFERENCE`  | `auth-router-error-reference` | `X-Custom-Error-Reference`  | Unique 8-character reference code.        |

#### Error Context and Recommendation

To simplify error handling in your Blade views, you can use the `SchenkeIo\LaravelAuthRouter\Auth\ErrorContext` DTO. It provides a convenient way to access all error data and localized recommendations.

```bladehtml
@use(SchenkeIo\LaravelAuthRouter\Auth\ErrorContext)
@php($error = ErrorContext::fromSession())

@if($error)
    <div class="error-container">
        <h1>{{ $error->category->name }}</h1>
        <p>{{ $error->info }}</p>
        
        @if($error->message)
            <p><small>{{ $error->message }}</small></p>
        @endif
        
        <div class="recommendation">
            <strong>What to do:</strong> {{ $error->recommendation() }}
        </div>
        
        <div class="footer">
            Error Code: {{ $error->type }} | Reference: {{ $error->reference }}
        </div>
    </div>
@endif
```

### Error Categories

Each error belongs to an `ErrorCategory` enum:

- `Configuration`: Issues with your `config/services.php` or environment.
- `Network`: Connectivity problems with the provider.
- `Account`: Issues related to the user's account or registration policy.
- `Session`: Expired sessions, CSRF (State) mismatches, or invalid requests.
- `Provider`: Errors returned directly by the third-party login provider.
- `Unknown`: Unexpected internal errors.

### Error Cases

The following error names are used in the `X-Custom-Error-Type` header and define the error's source:

| Error Name              | Description                                             |
|-------------------------|---------------------------------------------------------|
| `UnknownService`        | The requested login provider is unknown.                |
| `ServiceNotSet`         | The provider service is not defined in config.          |
| `ConfigNotSet`          | A specific config value (e.g., client_id) is missing.   |
| `UnableToAddNewUsers`   | New user registration is disabled in the macro call.    |
| `EmailMissing`          | The provider did not return an email address.           |
| `InvalidEmail`          | The returned email address is invalid.                  |
| `LocalAuth`             | Local authentication process failed.                    |
| `RemoteAuth`            | The third-party provider returned an error.             |
| `State`                 | OAuth state mismatch (potential CSRF).                  |
| `Network`               | A network error occurred during the callback.           |
| `InvalidRequest`        | The login or callback request was invalid.              |
| `MixedProviders`        | Mixing WorkOS and non-WorkOS providers is not allowed.  |
| `InvalidCredentials`    | The provided credentials (email/password) are incorrect. |