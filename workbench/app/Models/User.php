<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Traits\InteractsWithAuthRouter;
use Workbench\Database\Factories\UserFactory;

class User extends Authenticatable implements AuthenticatableRouterUser
{
    protected static string $factory = UserFactory::class;

    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithAuthRouter, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'provider',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
