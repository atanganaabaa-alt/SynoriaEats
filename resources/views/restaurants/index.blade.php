<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Restaurants') }}
                </h2>
                <p class="text-sm text-gray-500">
                    @if ($hasLocation)
                        Sélectionnés pour toi selon ta position, les prix et la disponibilité des livreurs.
                    @else
                        Nous préparons une sélection personnalisée dès que ta position est connue.
                    @endif
                </p>
            </div>
            <a href="{{ route('restaurants.preferences') }}"
               class="inline-flex items-center gap-2 rounded-full border border-synoria-yellow/50 bg-white px-4 py-2 text-sm font-medium text-synoria-ink shadow-sm hover:bg-synoria-yellow/10">
                <svg class="h-4 w-4 text-synoria-green" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                Personnaliser ma sélection
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($hasLocation)
                <div class="synoria-panel rounded-2xl px-5 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-synoria-ink">Sélection automatique active</p>
                        <p class="text-xs text-synoria-ink-soft mt-1">
                            @if ($hasCustomPreferences)
                                Profil personnalisé appliqué.
                            @else
                                Nous comparons distance, prix, frais de livraison, notes et livreurs disponibles.
                            @endif
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                        Position détectée
                    </span>
                </div>
            @else
                <div class="synoria-panel rounded-2xl px-5 py-4">
                    <p id="geo-status" class="text-sm text-synoria-ink-soft">Localisation en cours…</p>
                </div>
            @endif

            <form method="GET" class="synoria-panel rounded-2xl p-4">
                @if ($lat && $lng)
                    <input type="hidden" name="lat" value="{{ $lat }}">
                    <input type="hidden" name="lng" value="{{ $lng }}">
                @endif
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Rechercher un restaurant…"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />
                    <x-primary-button class="shrink-0">Rechercher</x-primary-button>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($restaurants as $restaurant)
                    <a href="{{ route('restaurants.show', $restaurant) }}"
                       class="block synoria-panel sm:rounded-2xl p-5 hover:ring-2 hover:ring-synoria-yellow/70 transition hover:-translate-y-0.5">
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
                            <div class="text-right shrink-0">
                                <span class="text-sm font-medium text-synoria-ink">★ {{ number_format($restaurant->rating, 1) }}</span>
                                @if (isset($restaurant->match_score))
                                    <p class="mt-1 text-xs font-semibold text-emerald-700">{{ $restaurant->match_score }}% match</p>
                                @endif
                            </div>
                        </div>

                        @if (! empty($restaurant->match_badges))
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($restaurant->match_badges as $badge)
                                    <span class="rounded-full bg-synoria-yellow/25 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-synoria-ink">
                                        {{ $badge }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-2 text-sm text-synoria-ink-soft line-clamp-2">{{ $restaurant->description }}</p>

                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-synoria-ink-faint">
                                {{ $restaurant->prep_time_min }} à {{ $restaurant->prep_time_max }} min
                            </span>
                            @if (isset($restaurant->estimated_fee))
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-synoria-ink-faint">
                                    livraison ~{{ number_format($restaurant->estimated_fee, 0, ',', ' ') }} FCFA
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-synoria-ink-faint">
                                    livraison {{ number_format($restaurant->delivery_fee, 0, ',', ' ') }} FCFA
                                </span>
                            @endif
                        </div>

                        @if (! empty($restaurant->match_highlights))
                            <ul class="mt-3 space-y-1 rounded-xl bg-emerald-50/70 px-3 py-2 text-xs text-synoria-green">
                                @foreach (array_slice($restaurant->match_highlights, 0, 4) as $highlight)
                                    <li>• {{ $highlight }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </a>
                @empty
                    <div class="col-span-full bg-white shadow-sm sm:rounded-lg p-8 text-center space-y-3 text-gray-500">
                        <p>Aucun restaurant disponible près de toi pour l’instant.</p>
                        <p class="text-sm">Essaie de personnaliser ta sélection ou élargis ta zone.</p>
                        <a href="{{ route('restaurants.preferences') }}" class="inline-flex text-sm font-medium text-emerald-700 hover:underline">
                            Ajuster mes préférences
                        </a>
                    </div>
                @endforelse
            </div>

            <div>{{ $restaurants->links() }}</div>
        </div>
    </div>

    @if (! $hasLocation)
        <script>
            (function () {
                const status = document.getElementById('geo-status');
                const go = (lat, lng) => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('lat', lat);
                    url.searchParams.set('lng', lng);
                    window.location.href = url.toString();
                };

                if (!navigator.geolocation) {
                    status.textContent = 'Géolocalisation indisponible. Les restaurants sont affichés par note.';
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (pos) => go(pos.coords.latitude.toFixed(6), pos.coords.longitude.toFixed(6)),
                    () => {
                        status.textContent = 'Position refusée. Affichage par note en attendant.';
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            })();
        </script>
    @endif
</x-app-layout>
