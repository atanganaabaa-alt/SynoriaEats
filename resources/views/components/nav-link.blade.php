@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-synoria-yellow text-sm font-medium leading-5 text-synoria-ink focus:outline-none focus:border-synoria-green transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-synoria-ink-soft hover:text-synoria-ink hover:border-synoria-yellow/50 focus:outline-none focus:text-synoria-ink focus:border-synoria-yellow/50 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
