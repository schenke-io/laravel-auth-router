<x-auth-router::provider>
    @php $success = $authRouterSuccess ?? session('authRouterSuccess'); @endphp
    @if($success)
        <div class="registration-success">
            <div class="success-icon">✓</div>
            <p>{{ $success }}</p>
        </div>
    @else
        <div class="auth-tabs">
            <button type="button" id="tab-login" class="tab-button active" onclick="showAuthMode('login')">
                {{__('auth-router::login.tab_login')}}
            </button>
            <button type="button" id="tab-register" class="tab-button" onclick="showAuthMode('register')">
                {{__('auth-router::login.tab_register')}}
            </button>
        </div>

        <form id="workos-auth-form" action="{{route($provider->loginRoute)}}" method="POST">
            @csrf
            <input type="hidden" name="action" id="auth-action" value="{{ old('action', 'login') }}">

            <div id="registration-fields" style="display: {{ old('action') === 'register' ? 'block' : 'none' }};">
                <div class="name-grid">
                    <div class="field-group">
                        <label>{{__('auth-router::login.first_name')}}</label>
                        <input name="first_name" type="text" class="input-style" placeholder="{{__('auth-router::login.first_name_placeholder')}}" value="{{ old('first_name') }}">
                        @error('first_name') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                    <div class="field-group">
                        <label>{{__('auth-router::login.last_name')}}</label>
                        <input name="last_name" type="text" class="input-style" placeholder="{{__('auth-router::login.last_name_placeholder')}}" value="{{ old('last_name') }}">
                        @error('last_name') <div class="error-msg">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="field-group">
                <label>{{__('auth-router::login.email_placeholder')}}</label>
                <input name="email" type="email" class="input-style" placeholder="{{__('auth-router::login.email_placeholder')}}" required value="{{ old('email') }}">
                @error('email') <div class="error-msg">{{ $message }}</div> @enderror
            </div>

            <div class="field-group">
                <label>{{__('auth-router::login.password')}}</label>
                <input name="password" type="password" class="input-style" placeholder="{{__('auth-router::login.workos_email.password_placeholder')}}" required>
                @error('password') <div class="error-msg">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="submit-btn" id="submit-button">
                {{ old('action') === 'register' ? __('auth-router::login.tab_register') : __('auth-router::login.tab_login') }}
            </button>
        </form>

        @if(old('action') === 'register')
            <script>document.addEventListener('DOMContentLoaded', () => showAuthMode('register'));</script>
        @endif
    @endif
</x-auth-router::provider>
