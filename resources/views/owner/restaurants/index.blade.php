<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <x-back-link :href="route('dashboard')" :label="__('Tableau de bord')" />
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">Mes restaurants</h2>
            </div>
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
                Un restaurant n’apparaît aux clients que s’il est <strong>approuvé</strong>, <strong>ouvert</strong>,
                et couvert par l’<strong>essai 30 jours</strong> ou un <strong>abonnement mensuel</strong> (Essentiel 15 000 / Pro 25 000 FCFA).
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
                                    · <span class="{{ $restaurant->hasCatalogAccess() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $restaurant->subscriptionLabel() }}</span>
                                    @if (! $restaurant->hasCatalogAccess())
                                        · <span class="text-amber-700">Pas visible</span>
                                    @elseif (! $restaurant->is_open)
                                        · <span class="text-amber-700">Fermé au catalogue</span>
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
                        <div class="flex flex-wrap items-center gap-2 text-sm shrink-0">
                            @if ($restaurant->isApproved())
                                <a href="{{ route('owner.restaurants.subscription', $restaurant) }}"
                                   class="inline-flex items-center rounded-md border-2 border-amber-400 bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-950 shadow-sm hover:bg-amber-200">
                                    {{ __('Abonnement') }}
                                </a>
                                <form method="POST" action="{{ route('owner.restaurants.update', $restaurant) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $restaurant->name }}">
                                    <input type="hidden" name="address" value="{{ $restaurant->address }}">
                                    <input type="hidden" name="is_open" value="{{ $restaurant->is_open ? '0' : '1' }}">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-md text-sm font-medium {{ $restaurant->is_open ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-600 text-white hover:bg-emerald-500' }}">
                                        {{ $restaurant->is_open ? __('Fermer') : __('Ouvrir au catalogue') }}
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('owner.restaurants.show', $restaurant) }}"
                               class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                                {{ __('Gérer') }} →
                            </a>
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
