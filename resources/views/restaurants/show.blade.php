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
                    Frais de livraison {{ number_format($restaurant->delivery_fee, 0, ',', ' ') }} FCFA ·
                    {{ $restaurant->opening_hours }}
                </p>
            </div>

            @php $grouped = $restaurant->menuItems->groupBy('category'); @endphp

            @forelse ($grouped as $category => $items)
                <section class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $category ?: 'Menu' }}</h3>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            <li class="py-3 flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                </div>
                                <p class="shrink-0 font-semibold text-emerald-700">
                                    {{ number_format($item->price, 0, ',', ' ') }} FCFA
                                </p>
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
