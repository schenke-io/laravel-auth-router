<x-auth-router::provider>
    @if($initiatePasskey ?? false)
        <div class="passkey-initiate" x-data="{}" x-init="console.log('Initiating passkey for {{ $email }}')">
             <p>Finalizing passkey authentication...</p>
             {{-- Here we would call spatie/laravel-passkeys JS --}}
             <script>
                 setTimeout(() => {
                     window.location.href = "{{ route($provider->callbackRoute) }}";
                 }, 2000);
             </script>
        </div>
    @elseif($showOtp ?? false)
        <div class="passkey-otp">
            <p class="select-provider">{{ __('auth-router::login.passkey.otp_message', ['email' => $email]) }}</p>
            <form action="{{ route($provider->loginRoute) }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="field-group">
                    {{-- flux:otp placeholder --}}
                    @if(View::exists('flux::otp'))
                        <flux:otp name="otp" />
                    @else
                        <input type="text" name="otp" maxlength="6" class="input-style" placeholder="123456" required autofocus style="text-align: center; font-size: 2rem; letter-spacing: 0.5rem;">
                    @endif
                </div>
                <button type="submit" class="submit-btn">
                    {{ __('auth-router::login.passkey.otp_submit') }}
                </button>
            </form>
        </div>
    @else
        <div x-data="{ passkeyAvailable: false }" x-init="passkeyAvailable = (window.PublicKeyCredential && window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable && await window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable())">
            <div x-show="passkeyAvailable" style="display: none">
                <x-auth-router::login-button :route="route($provider->loginRoute)" :text="__('auth-router::login.passkey.login_with_passkey')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 24px; height: 24px;">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3L15.5 7.5z"/>
                    </svg>
                </x-auth-router::login-button>
            </div>
            <div x-show="!passkeyAvailable" class="email-login-form">
                 <form action="{{ route($provider->loginRoute) }}" method="POST">
                     @csrf
                     <div class="field-group">
                         <input type="email" name="email" class="input-style" placeholder="{{ __('auth-router::login.passkey.email_placeholder') }}" required value="{{ $email ?? '' }}">
                     </div>
                     <button type="submit" class="submit-btn">
                         {{ __('auth-router::login.passkey.submit_button') }}
                     </button>
                 </form>
            </div>
        </div>
    @endif
</x-auth-router::provider>
