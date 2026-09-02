<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'SynoriaEats') }} · {{ __('Livraison de repas') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/synoria-icon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />
        <x-theme-init />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="synoria-shell">
            <header class="mx-auto max-w-5xl px-6 pt-8 flex flex-wrap items-center justify-between gap-4">
                <x-brand />
                <nav class="flex shrink-0 flex-wrap items-center justify-end gap-3 text-sm">
                    <x-ui-preferences />
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-synoria-green px-3 py-1.5 font-medium text-white hover:bg-synoria-green-dark">{{ __('Mon espace') }}</a>
                        <x-logout-button />
                    @else
                        <a href="{{ route('login') }}" class="text-synoria-ink-soft hover:text-synoria-ink dark:text-gray-300 dark:hover:text-white">{{ __('Connexion') }}</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-synoria-green px-3 py-1.5 font-medium text-white hover:bg-synoria-green-dark">{{ __('Inscription') }}</a>
                    @endauth
                </nav>
            </header>

            <main class="relative flex-1 mx-auto max-w-5xl px-6 py-10 md:py-14 space-y-12">
                {{-- Hero --}}
                <section class="grid items-center gap-6 lg:grid-cols-[1fr_minmax(0,17rem)] lg:gap-8">
                    <div class="order-2 lg:order-1">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-synoria-green">{{ __('Suite Synoria') }}</p>
                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-synoria-ink dark:text-white md:text-4xl">
                            {{ __('Tes plats préférés, livrés chez toi') }}
                        </h1>
                        <p class="mt-4 text-base text-synoria-ink-soft dark:text-gray-400 leading-relaxed">
                            {{ __('Poulet DG, ndolé, burgers, grillades. Découvre les restos ouverts près de toi.') }}
                        </p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('restaurants.index') }}" class="inline-flex items-center rounded-md bg-synoria-green px-4 py-2 text-sm font-semibold text-white hover:bg-synoria-green-dark">
                                {{ __('Voir les restaurants') }}
                            </a>
                            @guest
                                <a href="{{ route('register') }}" class="inline-flex items-center rounded-md border border-synoria-ink/10 bg-white/90 px-4 py-2 text-sm font-semibold text-synoria-ink hover:bg-synoria-yellow-soft dark:bg-slate-800 dark:border-slate-600 dark:text-gray-100">
                                    {{ __('Créer un compte') }}
                                </a>
                            @endguest
                        </div>
                    </div>

                    <div class="order-1 w-full max-w-[17rem] justify-self-center sm:max-w-xs lg:order-2 lg:max-w-none lg:justify-self-end">
                        <div class="overflow-hidden rounded-xl shadow-md ring-1 ring-synoria-yellow/30">
                            <img src="{{ asset('images/food/hero.jpg') }}" alt="{{ __('Plats livrés chez toi') }}"
                                 width="480" height="288" loading="eager"
                                 class="aspect-[5/3] max-h-44 w-full object-cover sm:max-h-48">
                        </div>
                        <div class="mt-1.5 grid grid-cols-3 gap-1.5">
                            @foreach ([
                                ['stew.jpg', __('Plats locaux')],
                                ['burger.jpg', __('Burgers')],
                                ['grill.jpg', __('Grillades')],
                            ] as [$file, $label])
                                <div class="overflow-hidden rounded-lg ring-1 ring-synoria-yellow/20">
                                    <img src="{{ asset('images/food/'.$file) }}" alt="{{ $label }}"
                                         width="120" height="90" loading="lazy"
                                         class="aspect-[4/3] w-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Galerie nourriture --}}
                <section class="space-y-3">
                    <h2 class="text-lg font-semibold text-synoria-ink dark:text-white">{{ __("Une envie ? On a ce qu'il faut") }}</h2>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 sm:max-w-xl">
                        @foreach ([
                            ['stew.jpg', __('Plats locaux')],
                            ['salad.jpg', __('Salades')],
                            ['pizza.jpg', __('Pizza')],
                            ['grill.jpg', __('Grillades')],
                        ] as [$file, $label])
                            <div class="overflow-hidden rounded-lg ring-1 ring-synoria-yellow/30">
                                <img src="{{ asset('images/food/'.$file) }}" alt="{{ $label }}" loading="lazy" width="128" height="96"
                                     class="aspect-[4/3] max-h-20 w-full object-cover sm:max-h-[5.5rem]">
                                <p class="bg-white px-2 py-1.5 text-center text-[11px] font-semibold text-synoria-ink dark:bg-slate-800 dark:text-gray-100">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Avantages --}}
                <section class="grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['🍽️', __('Menus locaux'), __('Plats camerounais et cuisines du quartier.')],
                        ['🛵', __('Livraison suivie'), __('Suis ta commande sur la carte.')],
                        ['💳', __('Paiement mobile'), __('Orange Money et MTN MoMo.')],
                    ] as [$emoji, $title, $desc])
                        <div class="synoria-panel rounded-xl p-4">
                            <span class="text-xl" aria-hidden="true">{{ $emoji }}</span>
                            <h2 class="mt-2 text-sm font-semibold text-synoria-ink dark:text-white">{{ $title }}</h2>
                            <p class="mt-1 text-xs text-synoria-ink-soft dark:text-gray-400">{{ $desc }}</p>
                        </div>
                    @endforeach
                </section>

                {{-- Restos --}}
                <section class="space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-synoria-ink dark:text-white">{{ __('Restaurants populaires') }}</h2>
                            <p class="text-xs text-synoria-ink-soft dark:text-gray-400">{{ __('Ouverts maintenant') }}</p>
                        </div>
                        <a href="{{ route('restaurants.index') }}" class="text-sm font-medium text-synoria-green hover:underline">{{ __('Tout voir') }}</a>
                    </div>

                    @if ($featured->isEmpty())
                        <div class="synoria-panel rounded-xl p-6 text-center">
                            <p class="text-2xl mb-2" aria-hidden="true">🍲</p>
                            <p class="text-sm font-medium text-synoria-ink dark:text-white">{{ __('Les restos arrivent bientôt') }}</p>
                            @guest
                                <a href="{{ route('register') }}" class="mt-3 inline-flex rounded-md bg-synoria-green px-3 py-1.5 text-sm font-semibold text-white hover:bg-synoria-green-dark">
                                    {{ __('Ouvrir mon restaurant') }}
                                </a>
                            @endguest
                        </div>
                    @else
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($featured as $restaurant)
                                @php
                                    $cover = $restaurant->coverThumbnailUrl() ?? $restaurant->logoThumbnailUrl();
                                    $dish = $restaurant->menuItems->first();
                                @endphp
                                <a href="{{ route('restaurants.show', $restaurant) }}" class="synoria-panel group flex gap-3 rounded-xl p-3 transition hover:shadow-sm">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-synoria-yellow/20">
                                        @if ($cover)
                                            <img src="{{ $cover }}" alt="" loading="lazy" width="56" height="56" class="h-full w-full object-cover">
                                        @else
                                            <img src="{{ asset('images/food/stew.jpg') }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-semibold text-synoria-ink dark:text-white group-hover:text-synoria-green">{{ $restaurant->name }}</h3>
                                        <p class="truncate text-xs text-synoria-ink-soft dark:text-gray-400">{{ $restaurant->address }}</p>
                                        @if ($dish)
                                            <p class="mt-0.5 text-xs text-synoria-green">{{ $dish->name }} · {{ number_format($dish->price, 0, ',', ' ') }} FCFA</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="synoria-panel rounded-xl p-5">
                    <h2 class="text-base font-semibold text-synoria-ink dark:text-white">{{ __('Comment commander ?') }}</h2>
                    <ol class="mt-3 grid gap-2 sm:grid-cols-3 text-xs text-synoria-ink-soft dark:text-gray-400">
                        @foreach ([
                            __('1. Choisis un restaurant'),
                            __('2. Ajoute tes plats au panier'),
                            __('3. Paie et suis la livraison'),
                        ] as $step)
                            <li class="flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-synoria-yellow text-[10px] font-bold text-synoria-ink">{{ $loop->iteration }}</span>
                                {{ $step }}
                            </li>
                        @endforeach
                    </ol>
                </section>
            </main>

            <x-site-footer />
        </div>
    </body>
</html>
