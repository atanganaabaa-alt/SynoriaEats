@props([
    'redirect' => 'home',
    'class' => 'text-sm font-medium text-synoria-ink-soft hover:text-synoria-ink underline underline-offset-2',
])

<form method="POST" action="{{ route('logout') }}" {{ $attributes->except('redirect')->merge(['class' => 'inline']) }}>
    @csrf
    <input type="hidden" name="redirect" value="{{ $redirect }}">
    <button type="submit" class="{{ $class }}">
        {{ $slot->isEmpty() ? __('Déconnexion') : $slot }}
    </button>
</form>
