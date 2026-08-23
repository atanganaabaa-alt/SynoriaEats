<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compagnon culinaire</h2>
            <p class="text-sm text-gray-500">
                Suggestions selon le menu, ton budget et l’attente de ta commande.
                @if ($restaurant)
                    Contexte : <strong>{{ $restaurant->name }}</strong>
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="synoria-panel rounded-2xl p-6 space-y-4">
                <p class="text-sm text-synoria-ink-soft">
                    Sans clé OpenAI, le compagnon utilise un moteur local (menu réel, budget, temps de préparation).
                    Ajoute <code class="text-xs">OPENAI_API_KEY</code> dans ton <code class="text-xs">.env</code> pour activer le mode IA.
                </p>

                @if ($context['active_order'] ?? null)
                    <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        Commande active {{ $context['active_order']['number'] }} —
                        {{ $context['active_order']['status_label'] }}
                        chez {{ $context['active_order']['restaurant'] }}.
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    @foreach ($suggestions as $chip)
                        <span class="rounded-full bg-synoria-yellow/20 px-3 py-1 text-xs font-medium text-synoria-ink">{{ $chip }}</span>
                    @endforeach
                </div>

                <a href="{{ route('restaurants.index') }}" class="inline-flex text-sm font-medium text-emerald-700 hover:underline">
                    Choisir un restaurant pour un conseil plus précis →
                </a>
            </div>

            @if (! empty($history))
                <div class="synoria-panel rounded-2xl p-6 space-y-3">
                    <h3 class="font-semibold text-synoria-ink">Conversation récente</h3>
                    @foreach ($history as $turn)
                        <div class="{{ ($turn['role'] ?? '') === 'user' ? 'text-right' : 'text-left' }}">
                            <div class="inline-block max-w-[90%] whitespace-pre-wrap rounded-2xl px-3 py-2 text-sm
                                {{ ($turn['role'] ?? '') === 'user'
                                    ? 'bg-synoria-green text-white'
                                    : 'bg-white border border-synoria-yellow/30 text-synoria-ink' }}">
                                {{ $turn['content'] ?? '' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-companion-chat :restaurant="$restaurant" :open="true" />
</x-app-layout>
