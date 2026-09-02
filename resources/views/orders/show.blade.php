<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('orders.index') }}" class="text-sm text-emerald-700 hover:underline dark:text-emerald-400">← {{ __('Mes commandes') }}</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">{{ $order->number }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-100 text-emerald-900 px-4 py-3 rounded-md text-sm dark:bg-emerald-900/40 dark:border-emerald-800 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2 dark:bg-slate-900 dark:text-gray-200" id="order-status-card">
                <p><span class="text-gray-500 dark:text-gray-400">{{ __('Restaurant') }} :</span> {{ $order->restaurant->name }}</p>
                <p><span class="text-gray-500 dark:text-gray-400">{{ __('Statut') }} :</span> <strong id="status-label">{{ $order->status->label() }}</strong></p>
                <p><span class="text-gray-500 dark:text-gray-400">{{ __('Paiement') }} :</span> {{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</p>
                <p><span class="text-gray-500 dark:text-gray-400">{{ __('Livraison') }} :</span> {{ $order->delivery_address }}</p>
                <p><span class="text-gray-500 dark:text-gray-400">{{ __('Téléphone') }} :</span> {{ $order->delivery_phone }}</p>
                @if ($order->courier)
                    <p><span class="text-gray-500 dark:text-gray-400">{{ __('Livreur') }} :</span> {{ $order->courier->name }}
                        @if ($order->courier->phone) · {{ $order->courier->phone }} @endif
                        · ★ {{ number_format($order->courier->rating ?? 0, 1) }}
                    </p>
                @endif
            </div>

            <x-live-map :order="$order" role="customer" />

            <x-order-timeline :order="$order" :poll="true" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 dark:bg-slate-900">
                <h3 class="font-semibold text-gray-900 mb-3 dark:text-white">{{ __('Articles') }}</h3>
                <ul class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach ($order->items as $item)
                        <li class="py-2 flex justify-between gap-3 text-sm dark:text-gray-200">
                            <span>{{ $item->quantity }}× {{ $item->name }}</span>
                            <span>{{ number_format($item->lineTotal(), 0, ',', ' ') }} FCFA</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-sm dark:border-slate-700 dark:text-gray-200">
                    <div class="flex justify-between"><span>{{ __('Sous-total') }}</span><span>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between"><span>{{ __('Livraison') }}</span><span>{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between font-semibold text-base"><span>{{ __('Total') }}</span><span>{{ number_format($order->total, 0, ',', ' ') }} FCFA</span></div>
                </div>
            </div>

            @if ($order->status === \App\Enums\OrderStatus::Delivered)
                @if ($order->review)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2 text-sm dark:bg-slate-900 dark:text-gray-200">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Ton avis') }}</h3>
                        <p>{{ __('Restaurant') }} : ★ {{ $order->review->restaurant_rating }}/5</p>
                        @if ($order->review->courier_rating)
                            <p>{{ __('Livreur') }} : ★ {{ $order->review->courier_rating }}/5</p>
                        @endif
                        @if ($order->review->comment)
                            <p class="text-gray-600 dark:text-gray-400">{{ $order->review->comment }}</p>
                        @endif
                    </div>
                @else
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 dark:bg-slate-900">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Noter ta commande') }}</h3>
                        <x-input-error :messages="$errors->get('review')" />
                        <form method="POST" action="{{ route('orders.reviews.store', $order) }}" class="space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="restaurant_rating" :value="__('Note restaurant (1 à 5)')" />
                                <x-text-input id="restaurant_rating" type="number" name="restaurant_rating" min="1" max="5" class="block mt-1 w-full" :value="old('restaurant_rating', 5)" required />
                            </div>
                            @if ($order->courier_id)
                                <div>
                                    <x-input-label for="courier_rating" :value="__('Note livreur (1 à 5)')" />
                                    <x-text-input id="courier_rating" type="number" name="courier_rating" min="1" max="5" class="block mt-1 w-full" :value="old('courier_rating', 5)" />
                                </div>
                            @endif
                            <div>
                                <x-input-label for="comment" :value="__('Commentaire (optionnel)')" />
                                <textarea id="comment" name="comment" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white">{{ old('comment') }}</textarea>
                            </div>
                            <x-primary-button>{{ __('Envoyer mon avis') }}</x-primary-button>
                        </form>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <x-companion-chat :restaurant="$order->restaurant" />
</x-app-layout>
