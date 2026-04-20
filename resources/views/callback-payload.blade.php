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
    <h1>{{ __('auth-router::login.payload_title') }}</h1>
    <p class="select-provider">{{ __('auth-router::login.payload_message') }}</p>

    <div style="text-align: left; margin: 20px 0; padding: 15px; background: rgba(0,0,0,0.05); border-radius: 8px;">
        <p><strong>{{ __('auth-router::login.name') }}:</strong> {{ $userData->name }}</p>
        <p><strong>{{ __('auth-router::login.email') }}:</strong> {{ $userData->email }}</p>
        <p><strong>{{ __('auth-router::login.provider') }}:</strong> {{ $userData->provider }}</p>
        @if($userData->avatar)
            <p><strong>{{ __('auth-router::login.avatar') }}:</strong></p>
            <img src="{{ $userData->avatar }}" alt="Avatar" style="max-width: 100px; border-radius: 50%;">
        @endif
    </div>

    <form action="{{ route($routeName) }}" method="POST">
        @csrf
        <button type="submit" class="provider-button" style="width: 100%; justify-content: center;">
            {{ __('auth-router::login.payload_submit') }}
        </button>
    </form>

    <div class="login-footer">
        <a href="{{ route($routeHome) }}" class="back-link">
            &larr; {{ __('auth-router::login.back_to_home') }}
        </a>
    </div>
</div>
</body>
</html>
