<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-emerald-700 hover:underline">← Audit commandes</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->number }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2 text-sm">
                <p><span class="text-gray-500">Restaurant :</span> {{ $order->restaurant->name }}</p>
                <p><span class="text-gray-500">Client :</span> {{ $order->customer->name }} ({{ $order->delivery_phone }})</p>
                <p><span class="text-gray-500">Livreur :</span> {{ $order->courier->name ?? '—' }}</p>
                <p><span class="text-gray-500">Statut :</span> <strong>{{ $order->status->label() }}</strong></p>
                <p><span class="text-gray-500">Paiement :</span> {{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</p>
                <p><span class="text-gray-500">Total :</span> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
            </div>

            <x-order-timeline :order="$order" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Notifications envoyées</h3>
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse ($order->notificationDeliveries as $delivery)
                        <li class="py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium">{{ $delivery->channel }} → {{ $delivery->recipient }}</span>
                                <span class="{{ $delivery->wasSent() ? 'text-emerald-700' : 'text-red-600' }}">
                                    {{ $delivery->wasSent() ? 'Envoyée' : 'Échec' }}
                                    · {{ $delivery->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <p class="text-gray-500 mt-1">{{ $delivery->message }}</p>
                            @if ($delivery->error)
                                <p class="text-red-600 text-xs mt-1">{{ $delivery->error }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="py-6 text-center text-gray-500">Aucune notification enregistrée.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
