<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('owner.restaurants.index') }}" class="text-sm text-emerald-700 hover:underline">← Mes restaurants</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $restaurant->name }}</h2>
            </div>
            <a href="{{ route('owner.restaurants.edit', $restaurant) }}" class="px-3 py-2 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Modifier le restaurant</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 text-red-800 px-4 py-3 rounded-md text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 space-y-1">
                <p>{{ $restaurant->address }}</p>
                <p>{{ $restaurant->category }} · {{ $restaurant->opening_hours }}</p>
                <p>Livraison de base {{ number_format($restaurant->delivery_fee, 0, ',', ' ') }} FCFA · {{ $restaurant->prep_time_min }}–{{ $restaurant->prep_time_max }} min</p>
                @if ($restaurant->menuItems->isEmpty())
                    <p class="text-amber-700">Ajoute plats, boissons ou accompagnements pour apparaître au catalogue.</p>
                @elseif (! $restaurant->is_validated)
                    <p class="text-amber-700">En attente de validation admin — pas encore visible au catalogue.</p>
                @elseif ($restaurant->is_open)
                    <p class="text-emerald-700">Visible dans le catalogue client.</p>
                @endif
                <p><a href="{{ route('restaurants.show', $restaurant) }}" class="text-emerald-700 hover:underline">Voir la page publique</a></p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Ajouter au menu</h3>
                <form method="POST" action="{{ route('owner.menu-items.store', $restaurant) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @include('owner.menu-items._form', ['categories' => \App\Enums\MenuCategory::cases()])
                    <div class="flex justify-end">
                        <x-primary-button>Ajouter</x-primary-button>
                    </div>
                </form>
            </div>

            @php
                $order = ['Plats', 'Accompagnements', 'Boissons', 'Desserts'];
                $grouped = $restaurant->menuItems->groupBy('category')->sortBy(fn ($items, $cat) => array_search($cat, $order, true) === false ? 99 : array_search($cat, $order, true));
            @endphp

            @foreach ($grouped as $category => $items)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ $category ?: 'Autres' }}</h3>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            <li class="py-3 flex items-start justify-between gap-4">
                                <div class="flex gap-3">
                                    @if ($item->photo_url)
                                        <img src="{{ str_starts_with($item->photo_url, 'http') ? $item->photo_url : asset('storage/'.$item->photo_url) }}"
                                             alt="" class="h-14 w-14 rounded object-cover shrink-0">
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                        <p class="text-xs {{ $item->is_available ? 'text-emerald-700' : 'text-gray-400' }}">
                                            {{ $item->is_available ? 'Disponible' : 'Indisponible' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right space-y-2">
                                    <p class="font-semibold text-emerald-700">{{ number_format($item->price, 0, ',', ' ') }} FCFA</p>
                                    <div class="flex gap-2 justify-end text-sm">
                                        <a href="{{ route('owner.menu-items.edit', $item) }}" class="text-gray-600 hover:underline">Modifier</a>
                                        <form method="POST" action="{{ route('owner.menu-items.destroy', $item) }}" onsubmit="return confirm('Supprimer ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:underline">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            @if ($restaurant->menuItems->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    Aucun article — ajoute des plats, boissons ou accompagnements.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
