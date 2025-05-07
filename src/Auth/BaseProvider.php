<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

abstract class BaseProvider
{
    public readonly string $name;

    public readonly string $loginUri;

    public readonly string $loginRoute;

    public readonly string $callbackUri;

    public readonly string $callbackRoute;

    public readonly ?Service $service;

    public string $blade = '';

    public string $href = '#';

    /**
     * @var string[]
     */
    protected array $errors = [];

    public function __construct()
    {
        $text = explode('Provider', class_basename($this));
        $this->service = Service::get(strtolower($text[0]));
        $this->name = $this->service->name ?? 'unknown';
        $this->loginUri = 'login/'.$this->name;
        $this->loginRoute = 'login.'.$this->name;
        $this->callbackUri = 'callback/'.$this->name;
        $this->callbackRoute = 'callback.'.$this->name;

        $longKey = 'services.'.$this->name;
        if (is_array(config($longKey, null))) {
            foreach (array_keys($this->env()) as $key) {
                $longKey = implode('.', ['services', $this->name, $key]);
                if (config($longKey) === null) {
                    $this->errors[] = __('auth-router::errors.config_not_set', ['key' => $longKey]);
                }
            }
        } else {
            $this->errors[] = __('auth-router::errors.service_not_set', ['name' => $this->name]);
        }

        $this->blade = 'auth-router::provider.'.($this->valid() ? $this->name : 'error');
    }

    /**
     * key: expected key in config(system), value: suggested name ov ENV key,
     * used in testing and in documentation
     *
     * @return array<string,string>
     */
    abstract public function env(): array;

    /*
     * ========================================================================
     *                  controller methods
     */

    public function fillMacro(RouterData $routerData): void
    {
        if (! $this->valid()) {
            return;
        }

        // make absolute URLs for redirect and callback URIs
        $fullRedirectUri = url($this->callbackUri);

        /*
         *  both methods can have any dependency injection
         *  but receive also a parameter over defaults()
         */
        $controller = 'SchenkeIo\\LaravelAuthRouter\\LoginProviders\\'.ucfirst($this->name).'Provider';

        Route::get($this->loginUri, $this->action('login'))
            ->name($this->loginRoute)
            ->defaults('fullRedirectUri', $fullRedirectUri)
            ->middleware(['guest']);

        Route::get($this->callbackUri, $this->action('callback'))
            ->name($this->callbackRoute)
            ->defaults('routerData', $routerData)
            ->middleware(['guest']);
    }

    private function action(string $method): string
    {
        return sprintf('SchenkeIo\\LaravelAuthRouter\\LoginProviders\\%sProvider@%s',
            ucfirst($this->name), $method
        );
    }

    /*
 * ========================================================================
 *                  error methods
 */

    public function addError(string $smg): void
    {
        $this->errors[] = $smg;
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function valid(): bool
    {
        return count($this->errors) === 0;
    }
}
