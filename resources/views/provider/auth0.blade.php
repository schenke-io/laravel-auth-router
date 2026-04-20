<x-auth-router::provider>
    <form action="{{route($provider->loginRoute)}}">
        <div class="field-group">
            <input name="hint"
                   type="email"
                   class="input-style"
                   placeholder="{{__('auth-router::login.auth0.email_placeholder')}}" required>
        </div>
        <button type="submit" class="submit-btn">
            {{__('auth-router::login.auth0.submit_button')}}
        </button>
    </form>
</x-auth-router::provider>