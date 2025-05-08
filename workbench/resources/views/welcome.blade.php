<!doctype html>
<html>
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
    </style>
</head>
<body>
<div class="login-container">
    @foreach(\SchenkeIo\LaravelAuthRouter\Auth\Service::cases() as $case)
        @include('auth-router::provider.' . $case->name,['provider' => $case->provider()])
    @endforeach
    @include('auth-router::provider.error',['provider' => $provider])
    <a href="#" class="back-button">{{__('auth-router::login.back')}}</a>
</div>
<div class="floating-buttons-container">
    @foreach(['en' => 'English', 'de' => 'Deutsch' ] as $locale => $text)
        <a class="{{$locale == App::getLocale() ? 'current':''}}"
           href="{{ route('set-language', ['lang' => $locale]) }}">{{$text}}</a>
    @endforeach
    <a href="/login">Login</a>
    <button id="button1">Dark</button>
    <button id="button2">Light</button>
    <script>
        function setLight() {
            document.documentElement.classList.remove('dark');
            window.localStorage.setItem('flux.appearance', 'dark');
        }

        function setDark() {
            document.documentElement.classList.add('dark')
            window.localStorage.setItem('flux.appearance', 'light')
        }

        document.getElementById('button1').addEventListener('click', () => setDark());
        document.getElementById('button2').addEventListener('click', () => setLight());
    </script>
    @include('auth-router::helper.script')
</div>
</body>
</html>
