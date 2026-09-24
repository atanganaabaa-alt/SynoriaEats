<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">{{ $agentName ?? 'Sara' }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Discussions enregistrées · moteur') }} :
                    <span class="font-medium text-synoria-green">{{ $engine ?? 'Local' }}</span>
                    @if ($restaurant)
                        · {{ __('Contexte') }} : <strong>{{ $restaurant->name }}</strong>
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if ($context['active_order'] ?? null)
                <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100">
                    {{ __('Commande active') }} {{ $context['active_order']['number'] }} :
                    {{ $context['active_order']['status_label'] }}
                    {{ __('chez') }} {{ $context['active_order']['restaurant'] }}.
                </div>
            @endif

            <x-companion-chat
                :restaurant="$restaurant"
                :embedded="true"
                :open="true"
                :threads="true"
                :initial-conversation-id="$activeConversationId"
            />
        </div>
    </div>
</x-app-layout>
