@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-synoria-yellow text-start text-base font-medium text-synoria-green bg-synoria-yellow/10 dark:bg-slate-800 dark:text-synoria-yellow transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-synoria-ink-soft dark:text-gray-400 hover:text-synoria-ink dark:hover:text-gray-100 hover:bg-synoria-yellow/10 dark:hover:bg-slate-800 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
