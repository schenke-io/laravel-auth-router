<x-auth-router::login-button :route="route($provider->loginRoute)" :text="__('auth-router::login.logto')">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
        <path fill="#5762d5" d="M24.2 38.2c-1.6-.4-3.1-.7-4.8-.7C11.3 37.5 4.6 30.8 4.6 22.5S11.3 7.5 19.4 7.5s14.8 6.7 14.8 15h4.8c0-10.7-8.7-19.4-19.6-19.4S0 11.8 0 22.5s8.7 19.4 19.4 19.4c1.7 0 3.3-.2 4.8-.6 1-.3 1.9-.7 2.8-1.2-2.3.9-4.4 2.2-6.1 4-1.8 1.8-3.2 3.9-4.2 6.3 1.1-.4 2.2-.9 3.2-1.4z" />
    </svg>
</x-auth-router::login-button>
