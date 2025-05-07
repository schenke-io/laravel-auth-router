<div class="social-button error-message">
    @foreach($provider->errors() as $text)
        {{$text}}<br>
    @endforeach
</div>