<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
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

    public function test_placeholder()
    {
        $this->assertTrue(true);
    }
}
