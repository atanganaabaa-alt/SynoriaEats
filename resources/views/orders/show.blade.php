<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('orders.index') }}" class="text-sm text-emerald-700 hover:underline">← Mes commandes</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->number }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2">
                <p><span class="text-gray-500">Restaurant :</span> {{ $order->restaurant->name }}</p>
                <p><span class="text-gray-500">Statut :</span> {{ $order->status->label() }}</p>
                <p><span class="text-gray-500">Paiement :</span> {{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</p>
                <p><span class="text-gray-500">Livraison :</span> {{ $order->delivery_address }}</p>
                <p><span class="text-gray-500">Téléphone :</span> {{ $order->delivery_phone }}</p>
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
        </div>
    </div>
</x-app-layout>
