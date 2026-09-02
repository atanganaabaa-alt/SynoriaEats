<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('restaurants.index') }}" class="text-sm text-synoria-green hover:underline">← {{ __('Restaurants') }}</a>
            <h2 class="font-semibold text-xl text-synoria-ink leading-tight dark:text-white">{{ $restaurant->name }}</h2>
            <p class="text-sm text-synoria-ink-soft">{{ $restaurant->address }} · ★ {{ number_format($restaurant->rating, 1) }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($restaurant->coverPublicUrl() || $restaurant->logoPublicUrl())
                <div class="synoria-panel sm:rounded-xl overflow-hidden">
                    @if ($restaurant->coverPublicUrl())
                        <div class="relative h-44 sm:h-52 bg-synoria-yellow-soft">
                            <img src="{{ $restaurant->coverThumbnailUrl() ?? $restaurant->coverPublicUrl() }}" alt="{{ $restaurant->name }}" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            @if ($restaurant->logoPublicUrl())
                                <img src="{{ $restaurant->logoThumbnailUrl() ?? $restaurant->logoPublicUrl() }}" alt="" loading="lazy" decoding="async" width="64" height="64" class="absolute bottom-3 left-4 h-16 w-16 rounded-xl ring-2 ring-white object-cover shadow-sm">
                            @endif
                        </div>
                    @elseif ($restaurant->logoPublicUrl())
                        <div class="p-4 flex items-center gap-3">
                            <img src="{{ $restaurant->logoPublicUrl() }}" alt="" class="h-16 w-16 rounded-xl object-cover ring-1 ring-synoria-yellow/40">
                            <p class="text-sm text-synoria-ink-soft">{{ __('Logo du restaurant') }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <div class="synoria-panel sm:rounded-lg p-6">
                <p class="text-synoria-ink-soft">{{ $restaurant->description }}</p>
                <p class="mt-3 text-sm text-synoria-ink-faint">
                    {{ __(':min à :max min', ['min' => $restaurant->prep_time_min, 'max' => $restaurant->prep_time_max]) }},
                    {{ __('frais de base :amount FCFA (ajustés à la distance au checkout)', ['amount' => number_format($restaurant->delivery_fee, 0, ',', ' ')]) }},
                    {{ $restaurant->opening_hours }}
                </p>
            </div>

            @php
                $order = \App\Enums\MenuCategory::clientCatalogOrder();
                $grouped = $restaurant->menuItems
                    ->groupBy('category')
                    ->sortBy(fn ($items, $cat) => array_search($cat, $order, true) === false ? 99 : array_search($cat, $order, true));
            @endphp

            @forelse ($grouped as $category => $items)
                <section class="synoria-panel sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-synoria-ink mb-1 dark:text-white">{{ $category ? __($category) : __('Menu') }}</h3>
                    @if ($category === \App\Enums\MenuCategory::Boissons->value)
                        <p class="text-sm text-synoria-ink-faint mb-4">{{ __('Commande tes boissons séparément des plats.') }}</p>
                    @endif
                    <ul class="divide-y divide-synoria-yellow/20">
                        @foreach ($items as $item)
                            <li class="py-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div class="flex gap-3">
                                    @if ($item->photoPublicUrl())
                                        <img src="{{ $item->photoThumbnailUrl() ?? $item->photoPublicUrl() }}"
                                             alt="" loading="lazy" decoding="async" width="64" height="64" class="h-16 w-16 rounded-lg object-cover shrink-0 ring-1 ring-synoria-yellow/30">
                                    @endif
                                    <div>
                                        <p class="font-medium text-synoria-ink dark:text-white">{{ $item->name }}</p>
                                        <p class="text-sm text-synoria-ink-soft">{{ $item->description }}</p>
                                        <p class="font-semibold text-synoria-green mt-1">
                                            {{ number_format($item->price, 0, ',', ' ') }} FCFA
                                        </p>
                                    </div>
                                </div>
                                @auth
                                    @if ($item->is_available)
                                        @php
                                            $isDish = $item->category === \App\Enums\MenuCategory::Plats->value;
                                            $accompOptions = $isDish ? $item->accompanimentOptions : collect();
                                        @endphp

                                        <form method="POST" action="{{ route('cart.store') }}" class="flex flex-col gap-2 shrink-0 sm:min-w-[14rem]">
                                            @csrf
                                            <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="quantity" value="1" min="1" max="10"
                                                       class="w-16 rounded-md border-gray-300 text-sm dark:bg-slate-800 dark:border-slate-600">
                                                <x-primary-button type="submit">{{ __('Ajouter') }}</x-primary-button>
                                            </div>

                                            @if ($isDish && $accompOptions->isNotEmpty())
                                                <div class="rounded-md border border-synoria-yellow/40 bg-synoria-yellow-mist/50 p-3 space-y-2 dark:bg-slate-800/50">
                                                    <p class="text-xs font-medium text-synoria-ink dark:text-gray-200">{{ __('Accompagnements (choix multiple) :') }}</p>
                                                    @foreach ($accompOptions as $accomp)
                                                        <label class="flex items-start gap-2 text-sm">
                                                            <input type="checkbox"
                                                                   name="accompaniment_ids[]"
                                                                   value="{{ $accomp->id }}"
                                                                   class="mt-0.5 rounded border-gray-300 text-synoria-green focus:ring-synoria-yellow">
                                                            <span class="text-synoria-ink-soft">
                                                                {{ $accomp->name }}
                                                                @php $extra = (int) ($accomp->pivot->extra_price ?? 0); @endphp
                                                                @if ($extra === 0)
                                                                    {{ __('(inclus)') }}
                                                                @else
                                                                    (+{{ number_format($extra, 0, ',', ' ') }} FCFA)
                                                                @endif
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </form>
                                    @else
                                        <span class="text-sm text-synoria-ink-faint">{{ __('Indisponible') }}</span>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="text-sm text-synoria-green hover:underline">{{ __('Connecte-toi pour commander') }}</a>
                                @endauth
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <div class="synoria-panel sm:rounded-lg p-8 text-center text-synoria-ink-soft">
                    {{ __('Menu bientôt disponible.') }}
                </div>
            @endforelse
        </div>
    </div>

    <x-companion-chat :restaurant="$restaurant" />
</x-app-layout>
