<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mes commandes') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @forelse ($orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="block bg-white shadow-sm sm:rounded-lg p-5 hover:ring-2 hover:ring-emerald-500/40 transition">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $order->number }}</p>
                            <p class="text-sm text-gray-500">{{ $order->restaurant->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-emerald-700">{{ $order->status->label() }}</p>
                            <p class="text-sm text-gray-600">{{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    Pas encore de commande. <a href="{{ route('restaurants.index') }}" class="text-emerald-700 hover:underline">Explorer les restaurants</a>
                </div>
            @endforelse

            <div>{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
