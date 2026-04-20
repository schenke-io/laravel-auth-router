<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;

/**
 * WhatsApp login provider implementation.
 *
 * This provider handles WhatsApp-specific authentication logic.
 */
class WhatsappProvider extends BaseProvider
{
    /**
     * @return array<string,string>
     */
    public function env(): array
    {
        return [
            'api_key' => 'WHATSAPP_API_KEY',
            'approved_emails' => 'WHATSAPP_APPROVED_EMAILS',
        ];
    }

    public function isSocial(): bool
    {
        return false;
    }

    public function login(RouterData $routerData): RedirectResponse|Response|View
    {
        $request = request();
        if ($request->isMethod('post')) {
            $email = $request->input('email');
            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->route($this->loginRoute)
                    ->withInput()
                    ->with('authRouterErrorMessage', 'Valid email is required');
            }

            // Check if email is approved
            $approvedEmails = config('services.whatsapp.approved_emails');
            if ($approvedEmails) {
                $approvedArray = array_map('trim', explode(',', $approvedEmails));
                if (! in_array($email, $approvedArray)) {
                    return redirect()->route($this->loginRoute)
                        ->withInput()
                        ->with('authRouterErrorMessage', 'Email not approved for WhatsApp login');
                }
            } else {
                return redirect()->route($this->loginRoute)
                    ->withInput()
                    ->with('authRouterErrorMessage', 'WhatsApp login is not configured with approved emails');
            }

            // Logic to send WhatsApp message would go here
            // For now, we simulate success and show waiting page
            return view('auth-router::provider.whatsapp-waiting', [
                'email' => $email,
                'routerData' => $routerData,
            ]);
        }

        return view('auth-router::login', [
            'providers' => ProviderCollection::fromTextArray([$this]),
            'routeHome' => $routerData->routeHome,
            'prefix' => $routerData->prefix,
        ]);
    }

    public function callback(RouterData $routerData): RedirectResponse|View
    {
        $email = request()->input('email');

        if (! $email) {
            return Error::InvalidRequest->redirect($routerData, 'Email missing in WhatsApp callback');
        }

        $userData = new UserData(
            name: explode('@', $email)[0],
            email: $email,
            provider: $this->name
        );

        return $userData->authAndRedirect($routerData);
    }
}
