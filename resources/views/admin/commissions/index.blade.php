<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 hover:underline">← Dashboard</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Commissions</h2>
                <p class="text-sm text-gray-500">Taux actuel : {{ number_format($rate * 100, 0) }} % (SYNORIA_COMMISSION_RATE)</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <x-input-label for="from" value="Du" />
                    <x-text-input id="from" type="date" name="from" class="block mt-1" :value="request('from')" />
                </div>
                <div>
                    <x-input-label for="to" value="Au" />
                    <x-text-input id="to" type="date" name="to" class="block mt-1" :value="request('to')" />
                </div>
                <x-primary-button>Filtrer</x-primary-button>
            </form>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Commandes payées</p>
                    <p class="text-2xl font-semibold">{{ $count }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">CA</p>
                    <p class="text-2xl font-semibold">{{ number_format($revenue, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Commissions</p>
                    <p class="text-2xl font-semibold text-emerald-700">{{ number_format($commissions, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Commande</th>
                            <th class="px-4 py-3 font-medium">Restaurant</th>
                            <th class="px-4 py-3 font-medium text-right">Total</th>
                            <th class="px-4 py-3 font-medium text-right">Commission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ $order->number }}</p>
                                    <p class="text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $order->restaurant->name }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($order->total, 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700 font-medium">{{ number_format($order->commission, 0, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Aucune commission sur cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
