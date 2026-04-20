<!doctype html>
<html class="{{ ($theme ?? session('workbench_form_data.color') ?? 'Light') == 'Dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{__('auth-router::login.page_title')}}</title>
    @include('auth-router::helper.script')
    @include('auth-router::helper.style')
</head>
<body>
<div class="login-card">
    <h1>{{__('auth-router::login.title')}}</h1>
    <p class="select-provider">{{__('auth-router::login.subtitle')}}</p>

    @if(session('authRouterErrorInfo'))
        <div class="alert alert-error">
            {{ session('authRouterErrorInfo') }}
            @if(session('authRouterErrorMessage'))
                <div class="error-detail">{{ session('authRouterErrorMessage') }}</div>
            @endif
        </div>
    @endif
    @php $success = $authRouterSuccess ?? session('authRouterSuccess'); @endphp
    @if($success)
        <div class="alert alert-success">
            {{ $success }}
        </div>
    @endif

    <div class="login-container">
        @php
            $emailProvider = $providers->first(fn($p) => !$p->isSocial());
            $socialProviders = $providers->filter(fn($p) => $p->isSocial());
        @endphp

        @if($emailProvider)
            <div class="provider-item">
                @include($emailProvider->blade, [
                    'provider' => $emailProvider,
                    'email' => $email ?? null,
                    'showOtp' => $showOtp ?? false,
                    'initiatePasskey' => $initiatePasskey ?? false
                ])
            </div>
        @endif

        @if($emailProvider && $socialProviders->count() > 0)
            <div class="separator">{{ __('auth-router::login.separator_text') }}</div>
        @endif

        @if($socialProviders->count() > 0)
            <div class="social-grid">
                @foreach($socialProviders as $provider)
                    @include($provider->blade, ['provider' => $provider])
                @endforeach
            </div>
        @endif
    </div>

    <div class="login-footer">
        <a href="{{route($routeHome)}}" class="back-link">
            &larr; {{__('auth-router::login.back_to_home')}}
        </a>
    </div>
</div>
</body>
</html>
