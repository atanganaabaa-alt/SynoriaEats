<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 hover:underline">← Dashboard</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Restaurants</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-col gap-3 sm:flex-row">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou adresse…"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <select name="validated" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Tous</option>
                    <option value="0" @selected(request('validated') === '0')>À valider</option>
                    <option value="1" @selected(request('validated') === '1')>Validés</option>
                </select>
                <x-primary-button>Filtrer</x-primary-button>
            </form>

            <div class="space-y-3">
                @forelse ($restaurants as $restaurant)
                    <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $restaurant->name }}</p>
                            <p class="text-sm text-gray-500">{{ $restaurant->address }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $restaurant->owner->name }} · {{ $restaurant->menu_items_count }} plats ·
                                <span class="{{ $restaurant->is_validated ? 'text-emerald-700' : 'text-amber-600' }}">
                                    {{ $restaurant->is_validated ? 'Validé' : 'En attente' }}
                                </span>
                                · {{ $restaurant->is_open ? 'Ouvert' : 'Fermé' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.restaurants.update', $restaurant) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_validated" value="{{ $restaurant->is_validated ? 0 : 1 }}">
                                <button class="px-3 py-1.5 text-sm rounded-md {{ $restaurant->is_validated ? 'bg-amber-100 text-amber-800' : 'bg-emerald-600 text-white' }}">
                                    {{ $restaurant->is_validated ? 'Retirer validation' : 'Valider' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.restaurants.update', $restaurant) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_open" value="{{ $restaurant->is_open ? 0 : 1 }}">
                                <button class="px-3 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">
                                    {{ $restaurant->is_open ? 'Fermer' : 'Ouvrir' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">Aucun restaurant.</div>
                @endforelse
            </div>
            <div>{{ $restaurants->links() }}</div>
        </div>
    </div>
</x-app-layout>
