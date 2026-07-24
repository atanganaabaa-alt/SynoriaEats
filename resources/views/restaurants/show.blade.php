<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('restaurants.index') }}" class="text-sm text-emerald-700 hover:underline">← Restaurants</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $restaurant->name }}</h2>
            <p class="text-sm text-gray-500">{{ $restaurant->address }} · ★ {{ number_format($restaurant->rating, 1) }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-600">{{ $restaurant->description }}</p>
                <p class="mt-3 text-sm text-gray-400">
                    {{ $restaurant->prep_time_min }}–{{ $restaurant->prep_time_max }} min ·
                    Frais de base {{ number_format($restaurant->delivery_fee, 0, ',', ' ') }} FCFA (ajustés à la distance au checkout) ·
                    {{ $restaurant->opening_hours }}
                </p>
            </div>

            @php
                $order = ['Plats', 'Accompagnements', 'Boissons', 'Desserts'];
                $grouped = $restaurant->menuItems->groupBy('category')->sortBy(fn ($items, $cat) => array_search($cat, $order, true) === false ? 99 : array_search($cat, $order, true));
            @endphp

            @forelse ($grouped as $category => $items)
                <section class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $category ?: 'Menu' }}</h3>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            <li class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex gap-3">
                                    @if ($item->photo_url)
                                        <img src="{{ str_starts_with($item->photo_url, 'http') ? $item->photo_url : asset('storage/'.$item->photo_url) }}"
                                             alt="" class="h-16 w-16 rounded object-cover shrink-0">
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                        <p class="font-semibold text-emerald-700 mt-1">
                                            {{ number_format($item->price, 0, ',', ' ') }} FCFA
                                        </p>
                                    </div>
                                </div>
                                @auth
                                    @if ($item->is_available)
                                        <form method="POST" action="{{ route('cart.store') }}" class="flex items-center gap-2 shrink-0">
                                            @csrf
                                            <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                                            <input type="number" name="quantity" value="1" min="1" max="10"
                                                   class="w-16 rounded-md border-gray-300 text-sm">
                                            <x-primary-button type="submit">Ajouter</x-primary-button>
                                        </form>
                                    @else
                                        <span class="text-sm text-gray-400">Indisponible</span>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="text-sm text-emerald-700 hover:underline">Connecte-toi pour commander</a>
                                @endauth
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    Menu bientôt disponible.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
