<?php

pest()->group('feature');

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

uses(LazilyRefreshDatabase::class);

it('logs a specific message when user creation fails due to a database constraint', function () {
    // Create a table where a field is NOT NULL and not handled by UserData
    Schema::create('strict_users', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->unique();
        $table->string('required_field');
        $table->timestamps();
    });

    // Define a temporary model
    $strictUserModel = new class extends User
    {
        protected $table = 'strict_users';

        protected $fillable = ['name', 'email'];
    };
    $strictUserModelClass = get_class($strictUserModel);

    $this->app->config->set('auth.providers.users.model', $strictUserModelClass);

    $userData = new UserData(
        name: '', // This should trigger NOT NULL constraint if empty string is treated as null or if we try to insert null
        email: 'strict@example.com',
        provider: 'google'
    );

    // In SQLite, an empty string is NOT NULL.
    // To trigger NOT NULL constraint failure, we might need to pass null.
    // But UserData->name is string.

    // Let's force a QueryException by another way if needed,
    // but the goal is to test the logging of the specific message.

    // If I want to trigger a NOT NULL failure in SQLite with an empty string,
    // I might need to make sure the adapter doesn't convert it.

    // Alternatively, a duplicate email will also trigger QueryException (Unique constraint).
    // The todo said "Identify if it's a database constraint violation (e.g., QueryException with certain SQL states/codes)."
    // "commonly name" was mentioned.

    Log::shouldReceive('error')
        ->withArgs(fn ($message) => str_contains($message, 'User creation failed on a NOT NULL/constraint column'))
        ->once();

    // We also expect the existing error logging or return
    // The current implementation returns Error::LocalAuth->redirect($routerData, $e->getMessage());

    $routerData = getRouterData(canAddNewUser: true);

    try {
        $userData->authAndRedirect($routerData);
    } catch (QueryException $e) {
        // if it's not caught
    } catch (Throwable $e) {
    }

});
