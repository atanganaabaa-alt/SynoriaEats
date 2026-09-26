<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <x-back-link :href="route('admin.dashboard')" label="Dashboard" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Abonnements restos</h2>
                <p class="text-sm text-gray-500">Essai {{ $trialDays }} j · Essentiel {{ number_format($plans[0]->yearlyPrice(), 0, ',', ' ') }} · Pro {{ number_format($plans[1]->yearlyPrice(), 0, ',', ' ') }} FCFA / an</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">En essai</p>
                    <p class="text-2xl font-semibold">{{ $stats['trial'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Abonnés payants</p>
                    <p class="text-2xl font-semibold text-emerald-700">{{ $stats['active'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-500">Expirés</p>
                    <p class="text-2xl font-semibold text-amber-700">{{ $stats['expired'] }}</p>
                </div>
            </div>

            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <x-input-label for="status" value="Filtrer" />
                    <select id="status" name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Tous (validés)</option>
                        <option value="trial" @selected(request('status') === 'trial')>Essai</option>
                        <option value="active" @selected(request('status') === 'active')>Payant</option>
                        <option value="expired" @selected(request('status') === 'expired')>Expiré</option>
                    </select>
                </div>
                <x-primary-button>Filtrer</x-primary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Restaurant</th>
                            <th class="px-4 py-3 font-medium">Statut</th>
                            <th class="px-4 py-3 font-medium">Fin d’accès</th>
                            <th class="px-4 py-3 font-medium">Action admin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($restaurants as $restaurant)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ $restaurant->name }}</p>
                                    <p class="text-gray-500">{{ $restaurant->owner?->email }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $restaurant->subscriptionLabel() }}</td>
                                <td class="px-4 py-3">
                                    {{ $restaurant->accessEndsAt()?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.subscriptions.update', $restaurant) }}" class="flex flex-wrap gap-2 items-center">
                                        @csrf
                                        @method('PATCH')
                                        <select name="plan" class="rounded-md border-gray-300 text-sm">
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->value }}" @selected($restaurant->subscription_plan === $plan)>
                                                    {{ $plan->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-2.5 py-1.5 rounded-md bg-emerald-600 text-white text-xs font-medium">
                                            +1 an
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Aucun restaurant validé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $restaurants->links() }}</div>
        </div>
    </div>
</x-app-layout>
