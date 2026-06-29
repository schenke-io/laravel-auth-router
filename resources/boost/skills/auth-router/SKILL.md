---
name: auth-router
description: Integrate, configure, and troubleshoot social authentication.
category: project
priority: high
---
# Skill: `auth-router`

## Purpose

Integrate, configure, and troubleshoot social authentication in a Laravel application with `schenke-io/laravel-auth-router`. The package registers all login, callback, and logout routes for every provider through a single `Route::authRouter()` macro, identifies users by email across providers, and reports failures as a structured, localised error context.

## When to Use

- Adding social login or multi-provider authentication to a Laravel app.
- Configuring or rotating provider credentials in `config/services.php` / `.env`.
- Diagnosing setup or runtime authentication errors and surfacing them to users.
- Enabling user impersonation for admin/support workflows.

## Sub-skills

This skill is split into focused files. Read the one that matches the task — they assume the overview below.

| File | Use it for |
| :--- | :--- |
| [`auth-router-integration.md`](sub-skills/auth-router-integration.md) | Registering routes via the `Route::authRouter()` macro, redirect targets, prefixes/names, impersonation. |
| [`auth-router-providers.md`](sub-skills/auth-router-providers.md) | Provider credentials and `config/services.php` entries (Socialite, WorkOS, Apple, Auth0, Logto). |
| [`auth-router-troubleshooting.md`](sub-skills/auth-router-troubleshooting.md) | Setup vs. runtime errors, the `Error`/`ErrorCategory`/`ErrorContext` model, reference codes, logging. |

## Overview

### The macro — only entry point

```php
use Illuminate\Support\Facades\Route;

Route::authRouter(['google', 'microsoft'])
    ->success('dashboard')   // redirect after successful login
    ->error('login')         // redirect after failure (reads ErrorContext)
    ->canAddUsers(false)     // false = existing users only (default true)
    ->prefix('auth')         // URI + route-name segment
    ->register();            // REQUIRED — nothing registers without it
```

### Key invariants

- **Always terminate with `->register()`** — omitting it silently skips registration.
- **Provider keys** come from `SchenkeIo\LaravelAuthRouter\Enums\Service` (case-insensitive, underscore-agnostic).
- **The `User` model must implement** `AuthenticatableRouterUser` (use the `InteractsWithAuthRouter` trait). Users are matched/created by **email**, stored against a single unified `provider_id` column.
- **Never put `redirect` in `config/services.php`** — the callback URL is injected per request from the named route.
- **Do not mix WorkOS with non-WorkOS providers** in one call — it raises `MixedProviders`.
- **Errors are structured.** Runtime failures redirect to the `->error()` route with a typed case, category, localised message, recommendation, and an 8-char reference code (see [`auth-router-troubleshooting.md`](sub-skills/auth-router-troubleshooting.md)).
