<?php

namespace SchenkeIo\LaravelAuthRouter\Enums;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Enumeration for all login and configuration errors.
 */
enum Error
{
    case UnknownService;
    case ServiceNotSet;
    case ConfigNotSet;
    case UnableToAddNewUsers;
    case EmailMissing;
    case InvalidEmail;
    case LocalAuth;
    case RemoteAuth;
    case State;
    case Network;
    case InvalidRequest;
    case MixedProviders;
    case LoginEmailError;
    case ExclusiveProvider;
    case InvalidCredentials;
    case InvalidToken;
    case ClosureNotCacheable;
    case EmailConfirmNotCacheable;

    /**
     * @param  array<string,string>  $errorParameter
     */
    public function redirect(RouterData $routerData, string $codeErrorMessage = '', array $errorParameter = []): RedirectResponse
    {
        $reference = $this->generateReference();
        $logData = [
            'type' => $this->name,
            'category' => $this->category()->value,
            'reference' => $reference,
            'info' => $this->trans($errorParameter),
            'message' => $codeErrorMessage,
        ];
        if ($routerData->logChannel) {
            Log::channel($routerData->logChannel)->error('AuthRouter error', $logData);
        } else {
            Log::error('[AuthRouter] error', $logData);
        }

        $briefMessage = $codeErrorMessage;
        if (str_contains($briefMessage, 'SQLSTATE')) {
            $briefMessage = $this->transDatabaseError();
        } elseif (strlen($briefMessage) > 100) {
            $briefMessage = substr($briefMessage, 0, 97).'...';
        }

        if ($routerData->routeError && Route::has($routerData->routeError)) {
            $redirect = Redirect::route($routerData->routeError);
        } else {
            $redirect = redirect('/');
        }

        return $redirect->withInput()
            ->with(SessionKey::ERROR_INFO, $this->trans($errorParameter))
            ->with(SessionKey::ERROR_MESSAGE, $briefMessage)
            ->with(SessionKey::ERROR_TYPE, $this->name)
            ->with(SessionKey::ERROR_CATEGORY, $this->category()->value)
            ->with(SessionKey::ERROR_REFERENCE, $reference)
            ->withHeaders([
                'X-Custom-Error-Type' => $this->name,
                'X-Custom-Error-Category' => $this->category()->value,
                'X-Custom-Error-Reference' => $reference,
            ]);
    }

    /**
     * @param  array<string,string>  $parameter
     */
    public function trans(array $parameter = []): string
    {
        if (app()->bound('translator')) {
            return __('auth-router::errors.'.$this->name, $parameter);
        }

        return $this->name;
    }

    private function transDatabaseError(): string
    {
        if (app()->bound('translator')) {
            return __('auth-router::errors.DatabaseError');
        }

        return 'Database error';
    }

    /**
     * @return string[]
     */
    public function parameterKeys(): array
    {
        return match ($this) {
            self::ServiceNotSet, self::UnknownService, self::ExclusiveProvider => ['name'],
            self::ConfigNotSet => ['key', 'env'],
            default => [],
        };

    }

    public function category(): ErrorCategory
    {
        return match ($this) {
            self::UnknownService,
            self::ServiceNotSet,
            self::ConfigNotSet,
            self::ClosureNotCacheable,
            self::EmailConfirmNotCacheable,
            self::ExclusiveProvider => ErrorCategory::Configuration,
            self::Network => ErrorCategory::Network,
            self::UnableToAddNewUsers,
            self::EmailMissing,
            self::InvalidEmail,
            self::LoginEmailError,
            self::InvalidCredentials => ErrorCategory::Account,
            self::LocalAuth,
            self::State,
            self::InvalidRequest,
            self::MixedProviders,
            self::InvalidToken => ErrorCategory::Session,
            self::RemoteAuth => ErrorCategory::Provider,
        };
    }

    private function generateReference(): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $ref = '';
        for ($i = 0; $i < 8; $i++) {
            if ($i === 4) {
                $ref .= '-';
            }
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $ref;
    }
}
