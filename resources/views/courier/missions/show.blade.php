<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('courier.missions.index') }}" class="text-sm text-emerald-700 hover:underline">← Missions</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->number }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif
            <x-input-error :messages="$errors->get('mission')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2 text-sm">
                <p><span class="text-gray-500">Statut :</span> <strong>{{ $order->status->label() }}</strong></p>
                <p><span class="text-gray-500">Restaurant :</span> {{ $order->restaurant->name }} — {{ $order->restaurant->address }}</p>
                <p><span class="text-gray-500">Client :</span> {{ $order->customer->name }} ({{ $order->delivery_phone }})</p>
                <p><span class="text-gray-500">Adresse :</span> {{ $order->delivery_address }}</p>
                @if ($order->courier_lat && $order->courier_lng)
                    <p><span class="text-gray-500">Ma position :</span> {{ $order->courier_lat }}, {{ $order->courier_lng }}</p>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-3">Articles</h3>
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($order->items as $item)
                        <li class="py-2 flex justify-between">
                            <span>{{ $item->quantity }}× {{ $item->name }}</span>
                            <span>{{ number_format($item->lineTotal(), 0, ',', ' ') }} FCFA</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                <h3 class="font-semibold">Actions</h3>

                @if ($order->courier_id === auth()->id() || auth()->user()->isAdmin())
                    <button type="button" id="share-location"
                            class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium hover:bg-gray-50">
                        Partager ma position GPS
                    </button>
                    <p id="location-status" class="text-xs text-gray-500"></p>

                    @if ($order->status === \App\Enums\OrderStatus::Ready)
                        <form method="POST" action="{{ route('courier.missions.pickup', $order) }}">
                            @csrf
                            <x-primary-button>J’ai récupéré la commande — démarrer la livraison</x-primary-button>
                        </form>
                    @endif

                    @if ($order->status === \App\Enums\OrderStatus::OutForDelivery)
                        <form method="POST" action="{{ route('courier.missions.deliver', $order) }}">
                            @csrf
                            <x-primary-button>Marquer comme livrée</x-primary-button>
                        </form>
                    @endif
                @elseif ($order->status === \App\Enums\OrderStatus::Ready && $order->courier_id === null)
                    <form method="POST" action="{{ route('courier.missions.claim', $order) }}">
                        @csrf
                        <x-primary-button>Prendre la mission</x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if ($order->courier_id === auth()->id() || auth()->user()->isAdmin())
        <script>
            document.getElementById('share-location')?.addEventListener('click', () => {
                const status = document.getElementById('location-status');
                if (!navigator.geolocation) {
                    status.textContent = 'Géolocalisation non supportée par ce navigateur.';
                    return;
                }
                status.textContent = 'Récupération de la position…';
                navigator.geolocation.getCurrentPosition(async (pos) => {
                    const res = await fetch(@json(route('courier.missions.location', $order)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude,
                        }),
                    });
                    status.textContent = res.ok
                        ? `Position envoyée (${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)})`
                        : 'Échec de l’envoi de la position.';
                }, () => {
                    status.textContent = 'Impossible d’obtenir la position. Autorise la géoloc.';
                });
            });
        </script>
    @endif
</x-app-layout>
