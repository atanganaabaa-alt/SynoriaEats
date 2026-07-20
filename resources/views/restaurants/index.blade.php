<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Restaurants') }}
                </h2>
                <p class="text-sm text-gray-500">Commande et livraison — suite Synoria</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Rechercher un restaurant…"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                />
                <select name="category" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Toutes catégories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <x-primary-button>Filtrer</x-primary-button>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($restaurants as $restaurant)
                    <a href="{{ route('restaurants.show', $restaurant) }}" class="block bg-white shadow-sm sm:rounded-lg p-5 hover:ring-2 hover:ring-emerald-500/40 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $restaurant->name }}</h3>
                                <p class="text-sm text-emerald-700">{{ $restaurant->category }}</p>
                            </div>
                            <span class="text-sm font-medium text-gray-700">★ {{ number_format($restaurant->rating, 1) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $restaurant->description }}</p>
                        <p class="mt-3 text-xs text-gray-400">
                            {{ $restaurant->prep_time_min }}–{{ $restaurant->prep_time_max }} min ·
                            Livraison {{ number_format($restaurant->delivery_fee, 0, ',', ' ') }} FCFA
                        </p>
                    </a>
                @empty
                    <div class="col-span-full bg-white shadow-sm sm:rounded-lg p-8 text-center space-y-3 text-gray-500">
                        <p>Aucun restaurant avec menu disponible pour l’instant.</p>
                        <p class="text-sm">Les restaurants apparaissent ici une fois que le restaurateur a ajouté des plats à son menu.</p>
                    </div>
                @endforelse
            </div>

            <div>{{ $restaurants->links() }}</div>
        </div>
    </div>
</x-app-layout>
