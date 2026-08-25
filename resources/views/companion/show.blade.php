<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $agentName ?? 'Amina' }}</h2>
            <p class="text-sm text-gray-500">
                Conseils selon le menu et ta position.
                @if ($restaurant)
                    Contexte : <strong>{{ $restaurant->name }}</strong>
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <p class="text-sm text-synoria-ink-soft px-1">
                Mode gratuit. Mémoire de conversation + restos proches de toi (si la géoloc est active sur /restaurants).
            </p>

            @if ($context['active_order'] ?? null)
                <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Commande active {{ $context['active_order']['number'] }} :
                    {{ $context['active_order']['status_label'] }}
                    chez {{ $context['active_order']['restaurant'] }}.
                </div>
            @endif

            <x-companion-chat :restaurant="$restaurant" :embedded="true" :open="true" />
        </div>
    </div>
</x-app-layout>
