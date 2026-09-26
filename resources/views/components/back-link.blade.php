@props([
    'href',
    'label' => null,
])

@php
    $text = $label ?? (string) $slot;
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center gap-1.5 text-sm font-medium text-synoria-green hover:text-synoria-green-dark hover:underline dark:text-emerald-400 dark:hover:text-emerald-300',
    ]) }}
>
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
    </svg>
    <span>{{ $text !== '' ? $text : __('Retour') }}</span>
</a>
