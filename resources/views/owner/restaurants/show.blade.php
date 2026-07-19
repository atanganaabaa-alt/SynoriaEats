<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('owner.restaurants.index') }}" class="text-sm text-emerald-700 hover:underline">← Mes restaurants</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $restaurant->name }}</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('owner.restaurants.edit', $restaurant) }}" class="px-3 py-2 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Modifier</a>
                <a href="{{ route('owner.menu-items.create', $restaurant) }}" class="px-3 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-500">Ajouter un plat</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 space-y-1">
                <p>{{ $restaurant->address }}</p>
                <p>{{ $restaurant->category }} · {{ $restaurant->opening_hours }}</p>
                <p>Livraison {{ number_format($restaurant->delivery_fee, 0, ',', ' ') }} FCFA · {{ $restaurant->prep_time_min }}–{{ $restaurant->prep_time_max }} min</p>
                <p><a href="{{ route('restaurants.show', $restaurant) }}" class="text-emerald-700 hover:underline">Voir la page publique</a></p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Menu</h3>
                <ul class="divide-y divide-gray-100">
                    @forelse ($restaurant->menuItems as $item)
                        <li class="py-3 flex items-start justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                <p class="text-xs {{ $item->is_available ? 'text-emerald-700' : 'text-gray-400' }}">
                                    {{ $item->is_available ? 'Disponible' : 'Indisponible' }}
                                </p>
                            </div>
                            <div class="text-right space-y-2">
                                <p class="font-semibold text-emerald-700">{{ number_format($item->price, 0, ',', ' ') }} FCFA</p>
                                <div class="flex gap-2 justify-end text-sm">
                                    <a href="{{ route('owner.menu-items.edit', $item) }}" class="text-gray-600 hover:underline">Modifier</a>
                                    <form method="POST" action="{{ route('owner.menu-items.destroy', $item) }}" onsubmit="return confirm('Supprimer ce plat ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-gray-500">Aucun plat. Ajoute ton premier menu.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
