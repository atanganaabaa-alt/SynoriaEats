<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('orders.index') }}" class="text-sm text-emerald-700 hover:underline">← Mes commandes</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->number }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-900 px-4 py-3 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2" id="order-status-card"
                 data-tracking-url="{{ route('orders.tracking', $order) }}"
                 data-poll="{{ in_array($order->status, [\App\Enums\OrderStatus::Ready, \App\Enums\OrderStatus::OutForDelivery], true) ? '1' : '0' }}">
                <p><span class="text-gray-500">Restaurant :</span> {{ $order->restaurant->name }}</p>
                <p><span class="text-gray-500">Statut :</span> <strong id="status-label">{{ $order->status->label() }}</strong></p>
                <p><span class="text-gray-500">Paiement :</span> {{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</p>
                <p><span class="text-gray-500">Livraison :</span> {{ $order->delivery_address }}</p>
                <p><span class="text-gray-500">Téléphone :</span> {{ $order->delivery_phone }}</p>
                @if ($order->courier)
                    <p><span class="text-gray-500">Livreur :</span> {{ $order->courier->name }}
                        @if ($order->courier->phone) · {{ $order->courier->phone }} @endif
                        · ★ {{ number_format($order->courier->rating ?? 0, 1) }}
                    </p>
                @endif
                <p id="courier-position" class="text-sm text-emerald-700 @if(! $order->courier_lat) hidden @endif">
                    Position livreur : <span id="courier-coords">{{ $order->courier_lat }}, {{ $order->courier_lng }}</span>
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Articles</h3>
                <ul class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <li class="py-2 flex justify-between gap-3 text-sm">
                            <span>{{ $item->quantity }}× {{ $item->name }}</span>
                            <span>{{ number_format($item->lineTotal(), 0, ',', ' ') }} FCFA</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-sm">
                    <div class="flex justify-between"><span>Sous-total</span><span>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between"><span>Livraison</span><span>{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between font-semibold text-base"><span>Total</span><span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span></div>
                </div>
            </div>

            @if ($order->status === \App\Enums\OrderStatus::Delivered)
                @if ($order->review)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2 text-sm">
                        <h3 class="font-semibold text-gray-900">Ton avis</h3>
                        <p>Restaurant : ★ {{ $order->review->restaurant_rating }}/5</p>
                        @if ($order->review->courier_rating)
                            <p>Livreur : ★ {{ $order->review->courier_rating }}/5</p>
                        @endif
                        @if ($order->review->comment)
                            <p class="text-gray-600">{{ $order->review->comment }}</p>
                        @endif
                    </div>
                @else
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                        <h3 class="font-semibold text-gray-900">Noter ta commande</h3>
                        <x-input-error :messages="$errors->get('review')" />
                        <form method="POST" action="{{ route('orders.reviews.store', $order) }}" class="space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="restaurant_rating" value="Note restaurant (1–5)" />
                                <x-text-input id="restaurant_rating" type="number" name="restaurant_rating" min="1" max="5" class="block mt-1 w-full" :value="old('restaurant_rating', 5)" required />
                            </div>
                            @if ($order->courier_id)
                                <div>
                                    <x-input-label for="courier_rating" value="Note livreur (1–5)" />
                                    <x-text-input id="courier_rating" type="number" name="courier_rating" min="1" max="5" class="block mt-1 w-full" :value="old('courier_rating', 5)" />
                                </div>
                            @endif
                            <div>
                                <x-input-label for="comment" value="Commentaire (optionnel)" />
                                <textarea id="comment" name="comment" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('comment') }}</textarea>
                            </div>
                            <x-primary-button>Envoyer mon avis</x-primary-button>
                        </form>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <script>
        const card = document.getElementById('order-status-card');
        if (card?.dataset.poll === '1') {
            const poll = async () => {
                try {
                    const res = await fetch(card.dataset.trackingUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    document.getElementById('status-label').textContent = data.status_label;
                    if (data.courier_lat && data.courier_lng) {
                        const box = document.getElementById('courier-position');
                        box.classList.remove('hidden');
                        document.getElementById('courier-coords').textContent = `${data.courier_lat}, ${data.courier_lng}`;
                    }
                    if (['delivered', 'cancelled'].includes(data.status)) {
                        clearInterval(timer);
                        window.location.reload();
                    }
                } catch (e) {}
            };
            const timer = setInterval(poll, 5000);
            poll();
        }
    </script>
</x-app-layout>
