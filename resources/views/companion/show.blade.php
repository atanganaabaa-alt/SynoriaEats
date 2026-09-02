<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">{{ $agentName ?? 'Sara' }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Ta conseillère pour trouver restos et plats près de toi.') }}
                @if ($restaurant)
                    {{ __('Contexte') }} : <strong>{{ $restaurant->name }}</strong>
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($context['active_order'] ?? null)
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100">
                    {{ __('Commande active') }} {{ $context['active_order']['number'] }} :
                    {{ $context['active_order']['status_label'] }}
                    {{ __('chez') }} {{ $context['active_order']['restaurant'] }}.
                </div>
            @endif

            <x-companion-chat :restaurant="$restaurant" :embedded="true" :open="true" />
        </div>
    </div>
</x-app-layout>
