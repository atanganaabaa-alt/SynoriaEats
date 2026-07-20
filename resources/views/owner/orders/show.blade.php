<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('owner.orders.index') }}" class="text-sm text-emerald-700 hover:underline">← Commandes</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->number }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2 text-sm">
                <p><span class="text-gray-500">Client :</span> {{ $order->customer->name }} ({{ $order->delivery_phone }})</p>
                <p><span class="text-gray-500">Adresse :</span> {{ $order->delivery_address }}</p>
                <p><span class="text-gray-500">Statut :</span> <strong>{{ $order->status->label() }}</strong></p>
                <p><span class="text-gray-500">Paiement :</span> {{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</p>
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
                <p class="mt-4 font-semibold text-right">{{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
            </div>

            @if ($nextStatuses !== [])
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-3">Mettre à jour le statut</h3>
                    <x-input-error :messages="$errors->get('status')" class="mb-2" />
                    <div class="flex flex-wrap gap-2">
                        @foreach ($nextStatuses as $status)
                            <form method="POST" action="{{ route('owner.orders.update', $order) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $status->value }}">
                                <button type="submit"
                                        class="px-4 py-2 rounded-md text-sm font-medium
                                        @if($status === \App\Enums\OrderStatus::Cancelled) bg-red-100 text-red-800 hover:bg-red-200
                                        @else bg-emerald-600 text-white hover:bg-emerald-500 @endif">
                                    {{ $status->label() }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
