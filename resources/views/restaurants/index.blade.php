<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Restaurants') }}
                </h2>
                <p class="text-sm text-gray-500">Commande et livraison: suite Synoria</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Rechercher un restaurant…"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:col-span-2"
                />
                <select name="category" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Toutes catégories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <select name="min_rating" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Note min.</option>
                    <option value="4" @selected(request('min_rating') === '4')>★ 4+</option>
                    <option value="4.5" @selected(request('min_rating') === '4.5')>★ 4.5+</option>
                </select>
                <select name="max_fee" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Frais max</option>
                    <option value="0" @selected(request('max_fee') === '0')>Gratuit</option>
                    <option value="500" @selected(request('max_fee') === '500')>≤ 500 FCFA</option>
                    <option value="1000" @selected(request('max_fee') === '1000')>≤ 1 000 FCFA</option>
                </select>
                <select name="sort" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="rating" @selected(request('sort', 'rating') === 'rating')>Mieux notés</option>
                    <option value="fee" @selected(request('sort') === 'fee')>Frais croissants</option>
                    <option value="prep" @selected(request('sort') === 'prep')>Plus rapides</option>
                    <option value="name" @selected(request('sort') === 'name')>Nom A à Z</option>
                </select>
                <div class="sm:col-span-2 lg:col-span-5">
                    <x-primary-button>Filtrer</x-primary-button>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($restaurants as $restaurant)
                    <a href="{{ route('restaurants.show', $restaurant) }}" class="block synoria-panel sm:rounded-lg p-5 hover:ring-2 hover:ring-synoria-yellow/70 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex gap-3 min-w-0">
                                @if ($restaurant->coverPublicUrl() || $restaurant->logoPublicUrl())
                                    <img src="{{ $restaurant->coverPublicUrl() ?? $restaurant->logoPublicUrl() }}"
                                         alt=""
                                         class="h-14 w-14 rounded-lg object-cover shrink-0 ring-1 ring-synoria-yellow/30">
                                @endif
                                <div class="min-w-0">
                                    <h3 class="text-lg font-semibold text-synoria-ink">{{ $restaurant->name }}</h3>
                                    <p class="text-sm text-synoria-green">{{ $restaurant->category }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-synoria-ink shrink-0">★ {{ number_format($restaurant->rating, 1) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-synoria-ink-soft line-clamp-2">{{ $restaurant->description }}</p>
                        <p class="mt-3 text-xs text-synoria-ink-faint">
                            {{ $restaurant->prep_time_min }} à {{ $restaurant->prep_time_max }} min,
                            livraison {{ number_format($restaurant->delivery_fee, 0, ',', ' ') }} FCFA
                        </p>
                    </a>
                @empty
                    <div class="col-span-full bg-white shadow-sm sm:rounded-lg p-8 text-center space-y-3 text-gray-500">
                        <p>Aucun restaurant avec menu disponible pour l’instant.</p>
                        <p class="text-sm">Les restaurants apparaissent ici une fois validés par l’admin, avec au moins un plat.</p>
                    </div>
                @endforelse
            </div>

            <div>{{ $restaurants->links() }}</div>
        </div>
    </div>
</x-app-layout>
