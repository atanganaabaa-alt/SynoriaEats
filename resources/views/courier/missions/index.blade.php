<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes missions</h2>
                <p class="text-sm text-gray-500">Note livreur ★ {{ number_format(auth()->user()->rating ?? 0, 1) }} · {{ auth()->user()->delivery_count }} livraisons</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif
            <x-input-error :messages="$errors->get('mission')" />

            <section class="space-y-3">
                <h3 class="font-semibold text-gray-900">En cours</h3>
                @forelse ($mine as $order)
                    <a href="{{ route('courier.missions.show', $order) }}" class="block bg-white shadow-sm sm:rounded-lg p-5 hover:ring-2 hover:ring-emerald-500/40">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $order->number }}</p>
                                <p class="text-sm text-gray-500">{{ $order->restaurant->name }} → {{ $order->delivery_address }}</p>
                            </div>
                            <span class="text-sm font-medium text-emerald-700">{{ $order->status->label() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-500">Aucune mission en cours.</div>
                @endforelse
            </section>

            <section class="space-y-3">
                <h3 class="font-semibold text-gray-900">Disponibles (prêtes)</h3>
                @forelse ($available as $order)
                    <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $order->number }}</p>
                            <p class="text-sm text-gray-500">{{ $order->restaurant->name }} · {{ $order->restaurant->address }}</p>
                            <p class="text-sm text-gray-500">Livraison : {{ $order->delivery_address }}</p>
                            <p class="text-sm font-medium text-emerald-700 mt-1">{{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <form method="POST" action="{{ route('courier.missions.claim', $order) }}">
                            @csrf
                            <x-primary-button>Prendre la mission</x-primary-button>
                        </form>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-500">Pas de commande prête pour le moment.</div>
                @endforelse
            </section>

            <section class="space-y-3">
                <h3 class="font-semibold text-gray-900">Historique récent</h3>
                @forelse ($history as $order)
                    <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm flex justify-between gap-3">
                        <span>{{ $order->number }} · {{ $order->restaurant->name }}</span>
                        <span class="text-gray-500">{{ $order->delivered_at?->format('d/m H:i') }}</span>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-500">Pas encore de livraison terminée.</div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
