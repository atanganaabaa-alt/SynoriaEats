<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 hover:underline">← Dashboard</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit des commandes</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 sm:grid-cols-4">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="N° commande, client, téléphone…"
                       class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:col-span-2">
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Tous statuts</option>
                    @foreach (\App\Enums\OrderStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <x-primary-button>Filtrer</x-primary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Commande</th>
                            <th class="px-4 py-3 font-medium">Restaurant</th>
                            <th class="px-4 py-3 font-medium">Client</th>
                            <th class="px-4 py-3 font-medium">Statut</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-emerald-700 hover:underline">{{ $order->number }}</a>
                                    <p class="text-xs text-gray-400">{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $order->restaurant->name }}</td>
                                <td class="px-4 py-3">{{ $order->customer->name }}</td>
                                <td class="px-4 py-3">{{ $order->status->label() }}</td>
                                <td class="px-4 py-3">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucune commande.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
