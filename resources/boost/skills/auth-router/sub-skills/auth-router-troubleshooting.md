---
name: auth-router-troubleshooting
description: Diagnose and resolve setup and runtime errors.
---
# Reference: Troubleshooting & Errors

Diagnose and resolve errors in the `laravel-auth-router` authentication flow. The package splits errors into two families and reports runtime errors as a **structured context**: a typed case, a category, a localised message, a recommendation, and a unique reference code.

## When to Use

- Setup errors appear on the `/login` provider-selector screen.
- A user reaches the `->error()` route after attempting to authenticate.
- An OAuth state mismatch, missing email, or token problem prevents login.
- The application needs to present localised error messages (and a support reference) to end users.

---

## Category A — Setup Errors (developer mistakes)

Detected **before** the OAuth flow and displayed directly on the login-selector screen.

**Symptom:** a red "Setup Errors" section on `/login`.

| Error | Cause | Fix |
| :--- | :--- | :--- |
| Missing `client_id` | Key absent from `config/services.php` | Add the key (see [`auth-router-providers.md`](auth-router-providers.md)) |
| Missing `client_secret` | Key absent or `.env` variable unset | Set the `.env` variable and re-cache config |
| Unknown provider key | Typo in the provider array | Check valid keys in `SchenkeIo\LaravelAuthRouter\Enums\Service` |
| Missing `->register()` | Macro chain not terminated | Append `->register()` in `routes/web.php` |

**Resolution flow:** read the label → cross-reference `config/services.php` and `.env` → apply the fix → run `php artisan config:clear` if `.env` changed → reload `/login` (errors clear immediately).

---

## Category B — Runtime Errors (OAuth / user-lifecycle)

Occur during or after the provider callback. The user is redirected to the `->error()` route. Every runtime error is **logged first** (via the `->logChannel()` channel if configured, otherwise `Log::error()` with an `[AuthRouter]` prefix) and then redirected.

### Step 1 — Read the error context

Prefer the `ErrorContext` DTO over raw session reads. It assembles every field and exposes the localised recommendation:

```blade
@use(SchenkeIo\LaravelAuthRouter\Auth\ErrorContext)
@php($error = ErrorContext::fromSession())

@if ($error)
    <div class="alert alert-danger" role="alert">
        <h2>{{ $error->category->name }}</h2>
        <p>{{ $error->info }}</p>                 {{-- localised, user-safe --}}
        <p>{{ $error->recommendation() }}</p>     {{-- actionable advice --}}
        @if ($error->message)
            <small>{{ $error->message }}</small>  {{-- masked/truncated technical detail --}}
        @endif
        <footer>{{ $error->type }} · Reference: {{ $error->reference }}</footer>
    </div>
@endif
```

`ErrorContext::fromSession()` returns `null` when no error is present (it requires at least `type`, `category`, and `reference`).

### Step 2 — Session keys and headers

Constants live on `SchenkeIo\LaravelAuthRouter\Auth\SessionKey` (all prefixed `auth-router-`). The type, category, and reference are **also** emitted as response headers for SPA/API clients.

| Session constant | Session key | Header | Content |
| :--- | :--- | :--- | :--- |
| `ERROR_INFO` | `auth-router-error-info` | — | Localised, user-safe message |
| `ERROR_MESSAGE` | `auth-router-error-message` | — | Technical message — `SQLSTATE` masked to `DatabaseError`, truncated to 100 chars |
| `ERROR_TYPE` | `auth-router-error-type` | `X-Custom-Error-Type` | `Error` enum case name |
| `ERROR_CATEGORY` | `auth-router-error-category` | `X-Custom-Error-Category` | `ErrorCategory` value |
| `ERROR_REFERENCE` | `auth-router-error-reference` | `X-Custom-Error-Reference` | 8-char code `XXXX-XXXX`, shared between log and UI |

The **reference code** appears in both the log entry and the UI — ask users to quote it, then grep your logs for it.

### Step 3 — Map the error case

Cases are `SchenkeIo\LaravelAuthRouter\Enums\Error`; categories are `ErrorCategory`.

| Case | Category | Meaning | Typical fix |
| :--- | :--- | :--- | :--- |
| `UnknownService` | Configuration | Provider key not in `Service` enum | Fix the key passed to `authRouter()` |
| `ServiceNotSet` | Configuration | No config array for the provider | Add a `services.php` entry |
| `ConfigNotSet` | Configuration | A required config key is empty | Set the missing `.env`/config value |
| `ExclusiveProvider` | Configuration | An exclusive provider mixed with others | Register the exclusive provider on its own |
| `Network` | Network | Connection error to provider | Retry; check outbound connectivity |
| `UnableToAddNewUsers` | Account | `canAddUsers(false)` and user absent | Create the user, or enable `canAddUsers(true)` |
| `EmailMissing` | Account | Provider returned no email | Enable the `email` scope for the provider |
| `InvalidEmail` | Account | Email fails `FILTER_VALIDATE_EMAIL` | Inspect the provider profile payload |
| `LoginEmailError` | Account | Login/OTP email could not be sent | Check mailer config and the address |
| `InvalidCredentials` | Account | Credentials rejected | Re-enter credentials |
| `LocalAuth` | Session | Socialite driver failed locally | Check session/cookie config |
| `State` | Session | OAuth state mismatch (possible CSRF) | Set `'stateless' => true` for the provider |
| `InvalidRequest` | Session | Malformed request | Retry the flow from `/login` |
| `MixedProviders` | Session | WorkOS mixed with non-WorkOS | Split into separate `authRouter()` calls |
| `InvalidToken` | Session | Provided token is invalid | Restart the login flow |
| `ClosureNotCacheable` | Configuration | Closure in `defaultName()` | Use a string strategy for `defaultName()` |
| `EmailConfirmNotCacheable` | Configuration | Handler not serialisable | Implement `__set_state()` in the handler |
| `RemoteAuth` | Provider | Provider returned an error response | Check the provider dashboard / status |

### Step 4 — Programmatic branching (optional)

For SPA/API clients, branch on the response headers:

```php
match ($request->header('X-Custom-Error-Category')) {
    'configuration' => abort(500, 'Auth is misconfigured.'),
    'account'       => redirect()->route('register'),
    default         => redirect()->route('login'),
};
```

Translation keys: `auth-router::errors.{Case}`, `auth-router::errors.category.{value}`, `auth-router::errors.recommendation.{value}`. Override via `lang/vendor/auth-router/`.

## Debugging Checklist

```
[ ] Visited /login — no "Setup Errors" shown
[ ] config/services.php has correct keys for all providers
[ ] .env variables set and `php artisan config:clear` run
[ ] ->register() terminates the authRouter() chain
[ ] Callback URL in the provider's OAuth app matches the route exactly
[ ] 'stateless' => true set for providers hitting State errors
[ ] Error view reads ErrorContext::fromSession() (info + recommendation + reference)
[ ] Reference code from the user's screen grepped in the configured log channel
```

## Design Notes — why the error model looks like this

Authentication fails in many ways, and each audience needs something different from a failure: a developer needs a precise cause, a user needs reassurance and a next step, and a support engineer needs to correlate what the user saw with what the logs recorded. The model is shaped around that:

- **Localised and user-safe by default.** `info` and the category `recommendation()` are translatable (`auth-router::errors.*`) and never leak internals. English and German ship in-box and are test-asserted complete for every case and category.
- **Technical detail is contained.** The raw `message` is kept apart from the user-facing `info`; `SQLSTATE` is masked to a generic `DatabaseError` and any long message is truncated to 100 chars — diagnostics stay in the logs, not the browser.
- **Correlation by reference code.** The same 8-char `XXXX-XXXX` code is written to the log and shown to the user, so support can find the exact log entry from what the user reports.
- **Dual transport.** State travels by both session (Blade views) and `X-Custom-Error-*` headers (SPA/API clients), so neither has to parse the other's format.
- **Configurable logging.** Errors log to the `->logChannel()` channel if set, else `Log::error()` with an `[AuthRouter]` prefix; success and registration events share that channel for one coherent audit trail.

The result: every auth failure becomes something a user can act on and a team can debug, without the application writing any error-handling plumbing of its own.

## Related

- [`../SKILL.md`](../SKILL.md) — overview and invariants.
- [`auth-router-integration.md`](auth-router-integration.md) — fix route registration issues.
- [`auth-router-providers.md`](auth-router-providers.md) — fix missing/incorrect provider configuration.
