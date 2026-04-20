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
    <h1>{{__('auth-router::login.success_title')}}</h1>
    <p class="select-provider">{{__('auth-router::login.success_message')}}</p>

    <div class="login-footer">
        <a href="{{route('home')}}" class="back-link">
            &larr; {{__('auth-router::login.back_to_home')}}
        </a>
    </div>
</div>
</body>
</html>
