<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard admin</h2>
                <p class="text-sm text-gray-500">
                    {{ $overview['from']->format('d/m/Y') }} → {{ $overview['to']->format('d/m/Y') }}
                    · Commission {{ number_format($overview['commission_rate'] * 100, 0) }} %
                </p>
            </div>
            <nav class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-50">Comptes</a>
                <a href="{{ route('admin.restaurants.index') }}" class="px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-50">Restaurants</a>
                <a href="{{ route('admin.commissions.index') }}" class="px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-50">Commissions</a>
            </nav>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <x-input-label for="from" value="Du" />
                    <x-text-input id="from" type="date" name="from" class="block mt-1" :value="request('from', $overview['from']->toDateString())" />
                </div>
                <div>
                    <x-input-label for="to" value="Au" />
                    <x-text-input id="to" type="date" name="to" class="block mt-1" :value="request('to', $overview['to']->toDateString())" />
                </div>
                <x-primary-button>Actualiser</x-primary-button>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">CA (payé)</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($overview['revenue'], 0, ',', ' ') }} <span class="text-sm font-normal">FCFA</span></p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Commissions</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($overview['commissions'], 0, ',', ' ') }} <span class="text-sm font-normal">FCFA</span></p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Commandes payées</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $overview['orders_paid'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Livraisons</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $overview['deliveries'] }}</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Satisfaction restos</p>
                    <p class="mt-1 text-2xl font-semibold">★ {{ number_format($overview['avg_restaurant_rating'], 1) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Satisfaction livreurs</p>
                    <p class="mt-1 text-2xl font-semibold">★ {{ number_format($overview['avg_courier_rating'], 1) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Utilisateurs</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $overview['users_total'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Restos à valider</p>
                    <p class="mt-1 text-2xl font-semibold {{ $overview['restaurants_pending'] > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $overview['restaurants_pending'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $overview['restaurants_active'] }} actifs au catalogue</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Dernières commandes</h3>
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse ($recentOrders as $order)
                        <li class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                            <span>{{ $order->number }} · {{ $order->restaurant->name }} · {{ $order->customer->name }}</span>
                            <span class="text-gray-500">{{ $order->status->label() }} · {{ number_format($order->total, 0, ',', ' ') }} FCFA</span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-gray-500">Aucune commande.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
