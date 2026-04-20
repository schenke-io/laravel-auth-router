<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;
use SchenkeIo\LaravelAuthRouter\Traits\InteractsWithAuthRouter;
use Workbench\App\Models\User;

class SimpleUser extends User implements AuthenticatableRouterUser
{
    use InteractsWithAuthRouter;

    protected $table = 'simple_users';

    protected $fillable = ['name', 'email', 'google_id', 'apple_id'];
}

class SimplificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('simple_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('google_id')->nullable();
            $table->string('apple_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_can_find_user_by_provider_id_field()
    {
        config(['auth.providers.users.model' => SimpleUser::class]);

        $user = SimpleUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'google_id' => 'google-123',
        ]);

        $userData = new UserData(
            name: 'John Updated',
            email: 'john-new@example.com',
            provider: 'google',
            providerId: 'google-123',
            providerIdField: 'google_id'
        );

        $routerData = getRouterData(true);
        $userData->authAndRedirect($routerData);

        $this->assertEquals($user->id, \Auth::id());
        $user->refresh();
        $this->assertEquals('john-new@example.com', $user->email);
    }

    public function test_it_works_without_interface_if_column_exists()
    {
        // We use the same table but a model that doesn't implement the interface
        $modelClass = new class extends User
        {
            protected $table = 'simple_users';

            protected $fillable = ['name', 'email', 'google_id'];
        };
        $modelClassName = get_class($modelClass);
        config(['auth.providers.users.model' => $modelClassName]);

        SimpleUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'google_id' => 'google-456',
        ]);

        $userData = new UserData(
            name: 'Jane Updated',
            email: 'jane@example.com',
            provider: 'google',
            providerId: 'google-456',
            providerIdField: 'google_id'
        );

        $routerData = getRouterData(true);
        $userData->authAndRedirect($routerData);

        $this->assertNotNull(\Auth::user());
        $this->assertEquals('jane@example.com', \Auth::user()->email);
    }
}
