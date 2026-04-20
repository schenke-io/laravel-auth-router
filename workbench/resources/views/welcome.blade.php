<!doctype html>
<html class="{{ ($theme ?? $sessionData['color'] ?? 'Light') == 'Dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Laravel Auth Router</title>
    @include('auth-router::helper.style')
    <style>
        /* Füge diesen CSS-Code hier ein */
        .floating-buttons-container {
            position: fixed; /* Positioniert relativ zum Viewport (Anzeigebereich) */
            top: 20px; /* Abstand vom oberen Rand des Viewports */
            right: 20px; /* Abstand vom rechten Rand des Viewports */
            z-index: 9999; /* Stellt sicher, dass die Buttons über allem liegen (ein hoher Wert) */
            display: flex; /* Ordnet die Buttons nebeneinander an */
            gap: 10px; /* Abstand zwischen den Buttons */
        }

        /* Optional: Grundlegendes Styling für die Buttons selbst innerhalb des Containers */
        .floating-buttons-container button {
            padding: 8px 15px; /* Innenabstand */
            border: 1px solid #ccc; /* Rahmen */
            background-color: #eee; /* Hintergrundfarbe */
            cursor: pointer; /* Mauszeiger ändern */
            border-radius: 5px; /* Abgerundete Ecken */
            font-size: 1rem; /* Schriftgröße */
        }

        .floating-buttons-container a {
            padding: 8px 15px; /* Innenabstand */
            border: 1px solid #ccc; /* Rahmen */
            background-color: silver; /* Hintergrundfarbe */
            cursor: pointer; /* Mauszeiger ändern */
            border-radius: 5px; /* Abgerundete Ecken */
            font-size: 1rem; /* Schriftgröße */
        }

        a.current {
            background-color: lightgreen; /* Hintergrundfarbe */
        }

        /* Füge weitere deiner bestehenden Styles hier oder in separaten Style-Blöcken hinzu */
        .login-card h1 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        .login-card h1:first-of-type {
            margin-top: 0;
        }
        .login-container {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        @if(session('success'))
            <div style="background: lightgreen; padding: 10px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #ccc; color: darkgreen;">
                {{ session('success') }}
            </div>
        @endif

        @php
            $configCombo = $sessionData['config_combo'] ?? 'Default';
            $loginRoute = match($configCombo) {
                'WhatsApp' => route('only-whatsapp.login'),
                'Social' => route('only-social.login'),
                'Mix' => route('mix.login'),
                'Error' => route('error.login'),
                default => route('login'),
            };
        @endphp

        <div style="background: #eef; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #ccd; text-align: center;">
            <a href="{{ $loginRoute }}" style="display: block; padding: 12px; background: #44a; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Go to {{ $configCombo }} Login
            </a>
        </div>

        <h2 style="margin-top: 0; font-size: 1.2rem;">Workbench Configuration</h2>
        <form action="{{ route('workbench.store') }}" method="POST" id="workbench-form">
            @csrf
            <div style="margin-bottom: 15px;">
                <label for="language" style="display: block; margin-bottom: 5px;">Language:</label>
                <select name="language" id="language" style="width: 100%; padding: 8px;" onchange="this.form.submit()">
                    <option value="en" {{ (old('language', $sessionData['language'] ?? '') == 'en') ? 'selected' : '' }}>English</option>
                    <option value="de" {{ (old('language', $sessionData['language'] ?? '') == 'de') ? 'selected' : '' }}>Deutsch</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="color" style="display: block; margin-bottom: 5px;">Color:</label>
                <select name="color" id="color" style="width: 100%; padding: 8px;" onchange="this.form.submit()">
                    <option value="Light" {{ (old('color', $sessionData['color'] ?? '') == 'Light') ? 'selected' : '' }}>Light</option>
                    <option value="Dark" {{ (old('color', $sessionData['color'] ?? '') == 'Dark') ? 'selected' : '' }}>Dark</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="config_combo" style="display: block; margin-bottom: 5px;">Config Combo:</label>
                <select name="config_combo" id="config_combo" style="width: 100%; padding: 8px;" onchange="this.form.submit()">
                    <option value="Default" {{ (old('config_combo', $sessionData['config_combo'] ?? '') == 'Default') ? 'selected' : '' }}>Default</option>
                    <option value="WhatsApp" {{ (old('config_combo', $sessionData['config_combo'] ?? '') == 'WhatsApp') ? 'selected' : '' }}>WhatsApp</option>
                    <option value="Social" {{ (old('config_combo', $sessionData['config_combo'] ?? '') == 'Social') ? 'selected' : '' }}>Social</option>
                    <option value="Mix" {{ (old('config_combo', $sessionData['config_combo'] ?? '') == 'Mix') ? 'selected' : '' }}>Mix</option>
                    <option value="Error" {{ (old('config_combo', $sessionData['config_combo'] ?? '') == 'Error') ? 'selected' : '' }}>Error</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                @foreach(['Default', 'WhatsApp', 'Social', 'Mix'] as $preset)
                    <button type="button" 
                            onclick="document.getElementById('config_combo').value = '{{ $preset }}'; document.getElementById('workbench-form').submit();"
                            style="padding: 8px; background: {{ $configCombo == $preset ? '#666' : '#eee' }}; color: {{ $configCombo == $preset ? 'white' : 'black' }}; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">
                        {{ $preset }}
                    </button>
                @endforeach
            </div>
        </form>

        <div class="login-footer" style="margin-top: 20px;">
            <a href="#" class="back-button">{{__('auth-router::login.back')}}</a>
        </div>
    </div>
</div>
<div class="floating-buttons-container">
    @foreach(['en' => 'English', 'de' => 'Deutsch' ] as $locale => $text)
        <a class="{{$locale == App::getLocale() ? 'current':''}}"
           href="{{ route('set-language', ['lang' => $locale]) }}">{{$text}}</a>
    @endforeach
    @include('auth-router::helper.script')
</div>
</body>
</html>
