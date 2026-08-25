<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes restaurants</h2>
            <a href="{{ route('owner.restaurants.create') }}" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-500">
                + Nouveau restaurant
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-synoria-ink-soft">
                Un restaurant n’apparaît aux clients que s’il est <strong>approuvé</strong> par l’admin <strong>et ouvert</strong>.
                Un « Nouveau restaurant » reste en attente jusqu’à validation — il ne contourne pas le contrôle admin.
            </p>

            @forelse ($restaurants as $restaurant)
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <a href="{{ route('owner.restaurants.show', $restaurant) }}" class="min-w-0 flex-1 hover:opacity-90">
                            <h3 class="font-semibold text-gray-900">{{ $restaurant->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $restaurant->address }}</p>
                            <p class="mt-1 text-xs">
                                @if ($restaurant->isApproved())
                                    <span class="text-emerald-700 font-medium">Approuvé</span>
                                    @if (! $restaurant->is_open)
                                        · <span class="text-amber-700">Fermé au catalogue</span> — ouvre-le pour que les clients le voient
                                    @else
                                        · <span class="text-emerald-700">Visible au catalogue</span>
                                    @endif
                                @elseif ($restaurant->status->value === 'rejected')
                                    <span class="text-red-700 font-medium">Rejeté</span>
                                @else
                                    <span class="text-amber-700 font-medium">En attente admin</span> — pas encore visible aux clients
                                @endif
                            </p>
                        </a>
                        <div class="flex items-center gap-3 text-sm shrink-0">
                            @if ($restaurant->isApproved())
                                <form method="POST" action="{{ route('owner.restaurants.update', $restaurant) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $restaurant->name }}">
                                    <input type="hidden" name="address" value="{{ $restaurant->address }}">
                                    <input type="hidden" name="is_open" value="{{ $restaurant->is_open ? '0' : '1' }}">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-md text-sm font-medium {{ $restaurant->is_open ? 'bg-slate-100 text-slate-700' : 'bg-emerald-600 text-white' }}">
                                        {{ $restaurant->is_open ? 'Fermer' : 'Ouvrir au catalogue' }}
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('owner.restaurants.show', $restaurant) }}" class="text-emerald-700 font-medium">Gérer →</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center space-y-4">
                    <p class="text-gray-500">Commence par créer ton restaurant, puis ajoute tes plats au menu.</p>
                    <a href="{{ route('owner.restaurants.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-500">
                        Créer mon restaurant
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
