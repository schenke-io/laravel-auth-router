@props(['route', 'text'])
<a href="{{ $route }}" {{ $attributes->merge(['class' => 'social-btn ' . (strtolower($theme ?? '') === 'dark' ? 'dark' : '')]) }}>
    {{ $slot }}
    <span>{{ $text }}</span>
</a>
