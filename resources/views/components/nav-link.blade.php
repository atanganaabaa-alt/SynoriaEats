@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-synoria-yellow text-sm font-medium leading-5 text-synoria-ink dark:text-gray-100 focus:outline-none focus:border-synoria-green transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-synoria-ink-soft dark:text-gray-400 hover:text-synoria-ink dark:hover:text-gray-100 hover:border-synoria-yellow/50 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
