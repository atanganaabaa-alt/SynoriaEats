<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes restaurants</h2>
            <a href="{{ route('owner.restaurants.create') }}" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-500">
                Nouveau restaurant
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            @forelse ($restaurants as $restaurant)
                <a href="{{ route('owner.restaurants.show', $restaurant) }}" class="block bg-white shadow-sm sm:rounded-lg p-5 hover:ring-2 hover:ring-emerald-500/40">
                    <div class="flex justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $restaurant->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $restaurant->address }}</p>
                        </div>
                        <span class="text-sm {{ $restaurant->is_open ? 'text-emerald-700' : 'text-gray-400' }}">
                            {{ $restaurant->is_open ? 'Ouvert' : 'Fermé' }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    Aucun restaurant. Crée ton profil restaurateur pour commencer.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
