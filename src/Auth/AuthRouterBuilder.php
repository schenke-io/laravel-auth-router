<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Contracts\EmailConfirmInterface;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Fluent builder for authentication routes.
 *
 * This class provides a chainable interface to configure and register
 * authentication routes for various login providers.
 */
class AuthRouterBuilder
{
    /**
     * The name of the route to redirect to on successful authentication.
     */
    protected string $routeSuccess = 'home';

    /**
     * The name of the route to redirect to on authentication error.
     */
    protected string $routeError = 'login';

    /**
     * The name of the home route.
     */
    protected string $routeHome = 'home';

    /**
     * Whether the application allows creating new users during login.
     */
    protected bool $canAddUsers = true;

    /**
     * Whether the "Remember Me" functionality should be enabled.
     */
    protected bool $rememberMe = false;

    /**
     * The URI prefix for the authentication routes.
     */
    protected string $prefix = '';

    /**
     * The base name for the generated routes.
     */
    protected ?string $routeName = null;

    /**
     * An optional email confirmation service.
     */
    protected ?EmailConfirmInterface $emailConfirm = null;

    /**
     * Middleware to apply to all registered authentication routes.
     *
     * @var string[]
     */
    protected array $middleware = [];

    /**
     * Whether to show the payload data before finalizing authentication.
     */
    protected bool $showPayload = false;

    /**
     * Tracks whether the routes have already been registered to avoid duplicates.
     */
    protected bool $isRegistered = false;

    /**
     * Initialize the builder with a set of login provider keys.
     *
     * @param  string|array<int, string>  $providerKeys  The identifiers for login providers (e.g., 'google', 'facebook').
     */
    public function __construct(protected string|array $providerKeys) {}

    /**
     * Set the redirect route name for successful authentication.
     *
     * @param  string  $route  The route name.
     * @return $this
     */
    public function success(string $route): self
    {
        $this->routeSuccess = $route;

        return $this;
    }

    /**
     * Set the redirect route name for authentication errors.
     *
     * @param  string  $route  The route name.
     * @return $this
     */
    public function error(string $route): self
    {
        $this->routeError = $route;

        return $this;
    }

    /**
     * Set the home route name for the application.
     *
     * @param  string  $route  The route name.
     * @return $this
     */
    public function home(string $route): self
    {
        $this->routeHome = $route;

        return $this;
    }

    /**
     * Configure whether the authentication system should allow adding new users.
     *
     * @param  bool  $can  True to allow user creation, false otherwise.
     * @return $this
     */
    public function canAddUsers(bool $can): self
    {
        $this->canAddUsers = $can;

        return $this;
    }

    /**
     * Configure whether "Remember Me" functionality should be active.
     *
     * @param  bool  $remember  True to enable "Remember Me".
     * @return $this
     */
    public function rememberMe(bool $remember): self
    {
        $this->rememberMe = $remember;

        return $this;
    }

    /**
     * Set a URI prefix for all routes registered by this builder.
     *
     * @param  string  $prefix  The URI prefix (e.g., 'auth').
     * @return $this
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Set a base name for all routes registered by this builder.
     *
     * @param  string  $name  The route name base.
     * @return $this
     */
    public function name(string $name): self
    {
        $this->routeName = $name;

        return $this;
    }

    /**
     * Provide an optional email confirmation interface for the authentication flow.
     *
     * @param  EmailConfirmInterface  $emailConfirm  The email confirmation handler.
     * @return $this
     */
    public function emailConfirm(EmailConfirmInterface $emailConfirm): self
    {
        $this->emailConfirm = $emailConfirm;

        return $this;
    }

    /**
     * Set the middleware to be applied to the authentication routes.
     *
     * @param  string|string[]  $middleware  One or more middleware names.
     * @return $this
     */
    public function middleware(string|array $middleware): self
    {
        $this->middleware = (array) $middleware;

        return $this;
    }

    /**
     * Configure whether to show the payload data before finalizing authentication.
     *
     * @param  bool  $show  True to show the payload.
     * @return $this
     */
    public function showPayload(bool $show = true): self
    {
        $this->showPayload = $show;

        return $this;
    }

    /**
     * Final method to register the configured routes.
     *
     * This method must be called to explicitly register the routes.
     * If not called, it will be automatically called on object destruction.
     */
    public function register(): void
    {
        if ($this->isRegistered) {
            return;
        }
        $this->isRegistered = true;

        $providers = ProviderCollection::fromTextArray($this->providerKeys);
        $routerData = new RouterData(
            $this->routeSuccess,
            $this->routeError,
            $this->routeHome,
            $this->canAddUsers,
            $this->rememberMe,
            $this->prefix,
            $this->routeName,
            $this->emailConfirm,
            $this->middleware,
            $this->showPayload
        );
        $authRouter = new AuthRouter;
        // add the routes for any provider
        $authRouter->addProviders($providers, $routerData);
        // add the login selector or redirect
        $authRouter->addLogin($providers, $routerData);
        // add the central logout
        $authRouter->addLogout($routerData);
        // add payload routes
        $authRouter->addPayloadRoutes($routerData);
        // ensures that the named routes are available in the current request
        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * Automatic route registration when the builder instance is no longer needed.
     *
     * This ensures that even if the caller doesn't call ->register() explicitly,
     * the routes are still registered as long as the builder is used as a temporary
     * object in a route file.
     */
    public function __destruct()
    {
        if (! $this->isRegistered) {
            $this->register();
        }
    }
}
