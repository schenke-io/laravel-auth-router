<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Contracts\PasskeyMailerInterface;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;

/**
 * Passkey login provider implementation.
 */
class PasskeyProvider extends BaseProvider
{
    /**
     * @return array<string,string>
     */
    public function env(): array
    {
        return [];
    }

    public function isSocial(): bool
    {
        return false;
    }

    public function login(RouterData $routerData): RedirectResponse|Response|View
    {
        $request = request();
        $email = $request->input('email');
        $otp = $request->input('otp');
        $showOtp = false;

        if ($request->isMethod('post')) {
            if ($otp) {
                // Step 3: Verify OTP
                $storedOtp = session('passkey_otp');
                $storedEmail = session('passkey_email');

                if ($otp === $storedOtp && $email === $storedEmail) {
                    // OTP valid, start passkey process
                    return view('auth-router::login', [
                        'providers' => ProviderCollection::fromTextArray([$this]),
                        'routeHome' => $routerData->routeHome,
                        'prefix' => $routerData->prefix,
                        'email' => $email,
                        'initiatePasskey' => true,
                    ]);
                } else {
                    return redirect()->route($this->loginRoute)
                        ->withInput()
                        ->with('authRouterErrorMessage', 'Invalid OTP');
                }
            }

            if ($email) {
                // Step 2: Send OTP
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return redirect()->route($this->loginRoute)
                        ->withInput()
                        ->with('authRouterErrorMessage', 'Valid email is required');
                }

                $otp = (string) rand(100000, 999999);
                session(['passkey_otp' => $otp, 'passkey_email' => $email]);

                /** @var PasskeyMailerInterface $mailer */
                $mailer = app(PasskeyMailerInterface::class);
                $mailer->sendMail($otp);

                $showOtp = true;
            }
        }

        // show login page (Step 1 or Step 2 retry)
        return view('auth-router::login', [
            'providers' => ProviderCollection::fromTextArray([$this]),
            'routeHome' => $routerData->routeHome,
            'prefix' => $routerData->prefix,
            'email' => $email,
            'showOtp' => $showOtp,
        ]);
    }

    public function callback(RouterData $routerData): RedirectResponse|View
    {
        // finalize authentication via spatie/laravel-passkeys
        // for now, a placeholder
        $email = session('passkey_email');
        if (! $email) {
            return redirect()->route($this->loginRoute)->with('authRouterErrorMessage', 'Session expired');
        }

        $userData = new UserData(
            name: explode('@', $email)[0],
            email: $email,
            provider: $this->name,
            providerId: 'passkey-'.md5($email),
            providerIdField: $this->getProviderIdField()
        );

        return $userData->authAndRedirect($routerData);
    }
}
