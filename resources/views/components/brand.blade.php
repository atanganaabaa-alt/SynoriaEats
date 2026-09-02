@props(['withWordmark' => true, 'size' => 'md', 'markOnly' => false])

@php
    $iconClass = match ($size) {
        'sm' => 'h-9 w-9',
        'lg' => 'h-16 w-16',
        'xl' => 'h-24 w-24',
        default => 'h-11 w-11',
    };
    $textClass = match ($size) {
        'sm' => 'text-base',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
        default => 'text-lg',
    };
@endphp

<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 group']) }}>
    <img
        src="{{ asset('images/synoria-mark.png') }}"
        alt="Synoria"
        class="{{ $iconClass }} rounded-2xl object-cover shadow-sm ring-1 ring-synoria-ink/10 group-hover:shadow-synoria transition duration-200"
    >
    @if ($withWordmark && ! $markOnly)
        <span class="{{ $textClass }} font-semibold tracking-tight text-synoria-ink dark:text-white leading-none">
            Synoria<span class="text-synoria-green">Eats</span>
        </span>
    @endif
</a>
