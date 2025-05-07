<!doctype html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Laravel Auth Router</title>
    @include('auth-router::helper.script')
    @include('auth-router::helper.style')
</head>
<body>
<div class="login-container">

    @foreach($providers as $provider)
        @include($provider->blade,['provider' => $provider])
    @endforeach
    <a href="{{route($routeHome)}}" class="back-button">{{__('auth-router::login.back')}}</a>
</div>
</body>
</html>
