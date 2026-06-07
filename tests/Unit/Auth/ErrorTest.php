<?php

use Illuminate\Support\Facades\Log;
use SchenkeIo\LaravelAuthRouter\Auth\ErrorContext;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use SchenkeIo\LaravelAuthRouter\Enums\Error;
use SchenkeIo\LaravelAuthRouter\Enums\ErrorCategory;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__)->group('unit');

it('returns null if session keys are missing', function () {
    expect(ErrorContext::fromSession())->toBeNull();
});

it('can be created from session', function () {
    session([
        SessionKey::ERROR_TYPE => 'SomeError',
        SessionKey::ERROR_CATEGORY => ErrorCategory::Configuration->value,
        SessionKey::ERROR_REFERENCE => 'XXXX-XXXX',
        SessionKey::ERROR_INFO => 'Some info',
        SessionKey::ERROR_MESSAGE => 'Some message',
    ]);

    $context = ErrorContext::fromSession();

    expect($context)->not->toBeNull()
        ->and($context->type)->toBe('SomeError')
        ->and($context->category)->toBe(ErrorCategory::Configuration)
        ->and($context->reference)->toBe('XXXX-XXXX')
        ->and($context->info)->toBe('Some info')
        ->and($context->message)->toBe('Some message');
});

it('correctly maps errors to categories', function () {
    expect(Error::UnknownService->category())->toBe(ErrorCategory::Configuration)
        ->and(Error::ServiceNotSet->category())->toBe(ErrorCategory::Configuration)
        ->and(Error::ConfigNotSet->category())->toBe(ErrorCategory::Configuration)
        ->and(Error::ExclusiveProvider->category())->toBe(ErrorCategory::Configuration)
        ->and(Error::Network->category())->toBe(ErrorCategory::Network)
        ->and(Error::UnableToAddNewUsers->category())->toBe(ErrorCategory::Account)
        ->and(Error::EmailMissing->category())->toBe(ErrorCategory::Account)
        ->and(Error::InvalidEmail->category())->toBe(ErrorCategory::Account)
        ->and(Error::LoginEmailError->category())->toBe(ErrorCategory::Account)
        ->and(Error::InvalidCredentials->category())->toBe(ErrorCategory::Account)
        ->and(Error::LocalAuth->category())->toBe(ErrorCategory::Session)
        ->and(Error::State->category())->toBe(ErrorCategory::Session)
        ->and(Error::InvalidRequest->category())->toBe(ErrorCategory::Session)
        ->and(Error::MixedProviders->category())->toBe(ErrorCategory::Session)
        ->and(Error::InvalidToken->category())->toBe(ErrorCategory::Session)
        ->and(Error::RemoteAuth->category())->toBe(ErrorCategory::Provider);
});

it('generates a valid reference', function () {
    $error = Error::LocalAuth;
    $reflection = new ReflectionClass($error);
    $method = $reflection->getMethod('generateReference');
    $method->setAccessible(true);

    $ref = $method->invoke($error);

    expect($ref)->toMatch('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{4}$/');
});

it('includes new fields in redirect session and headers', function () {
    $routerData = getRouterData(true);

    $response = Error::LocalAuth->redirect($routerData, 'test error');

    expect(session(SessionKey::ERROR_TYPE))->toBe('LocalAuth')
        ->and(session(SessionKey::ERROR_CATEGORY))->toBe(ErrorCategory::Session->value)
        ->and(session(SessionKey::ERROR_REFERENCE))->not->toBeNull();

    $ref = session(SessionKey::ERROR_REFERENCE);
    expect($ref)->toMatch('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{4}$/');

    expect($response->headers->get('X-Custom-Error-Type'))->toBe('LocalAuth');
    expect($response->headers->get('X-Custom-Error-Category'))->toBe(ErrorCategory::Session->value);
    expect($response->headers->get('X-Custom-Error-Reference'))->toBe($ref);
});

it('logs errors with fall-back to Log::error', function () {
    $routerData = getRouterData(true);
    $routerData->logChannel = null;

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === '[AuthRouter] error' &&
                $context['type'] === 'LocalAuth' &&
                $context['category'] === ErrorCategory::Session->value &&
                isset($context['reference']);
        });

    Error::LocalAuth->redirect($routerData, 'test error');
});

it('has translations for all error cases in English and German', function (string $locale) {
    app()->setLocale($locale);
    foreach (Error::cases() as $case) {
        $translation = $case->trans();
        expect($translation)
            ->not->toBe($case->name)
            ->and($translation)->not->toBe("auth-router::errors.{$case->name}");
    }
})->with(['en', 'de']);

it('has recommendations and category labels for all categories in English and German', function (string $locale) {
    app()->setLocale($locale);
    foreach (ErrorCategory::cases() as $category) {
        $recommendation = $category->recommendation();
        expect($recommendation)
            ->not->toBeEmpty()
            ->and($recommendation)->not->toBe("auth-router::errors.recommendation.{$category->value}");

        $label = __("auth-router::errors.category.{$category->value}");
        expect($label)
            ->not->toBeEmpty()
            ->and($label)->not->toBe("auth-router::errors.category.{$category->value}");
    }
})->with(['en', 'de']);

it('returns recommendation from ErrorContext', function () {
    app()->setLocale('en');
    $context = new ErrorContext(
        'SomeError',
        ErrorCategory::Configuration,
        'XXXX-XXXX',
        'info',
        'message'
    );

    expect($context->recommendation())->toBe('Contact the system administrator to check the environment settings.');
});
