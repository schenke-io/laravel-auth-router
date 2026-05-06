## Errors

The package handles two types of errors differently:

1) setup errors the developer can handle
2) runtime errors which influence the user experience

### Setup errors

The setup errors are shown in the `/login` selector page. 
If you have any errors with your setup, you can fix them in `config/services.php`.
Its mainly missing keys, or missing provider names in `config/services.php`.

### Runtime errors

The runtime errors are stored in a session and can be handled by the app.
All session keys are defined as constants in the `SchenkeIo\LaravelAuthRouter\Auth\SessionKey` class.

| constant                | value                  | value                               | language       | header                 |
|-------------------------|------------------------|-------------------------------------|----------------|------------------------|
| `ERROR_INFO`            | auth-router-error-info    | user message of the error           | localised      |                        |
| `ERROR_MESSAGE`         | auth-router-error-message | exception text of the provider/code | english mainly |                        |
|                         |                        | name of the error case              |                | X-Custom-Error-Type    |

The error page could look like:

```bladehtml
@use(SchenkeIo\LaravelAuthRouter\Auth\SessionKey)
<h3>
    {{session(SessionKey::ERROR_INFO)}}
</h3>
<p>
    {{session(SessionKey::ERROR_MESSAGE)}}
</p>
```

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