<form class="email-login-group" action="{{route($provider->loginRoute)}}">
    <input name="hint"
           type="email"
           class="email-input"
           placeholder="{{__('auth-router::login.auth0.email_placeholder')}}" required>
    <button type="submit" class="email-submit-button">
        {{__('auth-router::login.auth0.submit_button')}}
    </button>
</form>