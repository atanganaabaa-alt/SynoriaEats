<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'SynoriaEats') }}: livraison de repas</title>
        <link rel="icon" type="image/png" href="{{ asset('images/synoria-icon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-synoria-ink">
        <div class="synoria-shell">
            <header class="mx-auto max-w-5xl px-6 pt-8 flex items-center justify-between">
                <x-brand />
                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('restaurants.index') }}" class="text-synoria-ink-soft hover:text-synoria-ink">Restaurants</a>
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-synoria-green px-3 py-1.5 font-medium text-white hover:bg-synoria-green-dark">Tableau de bord</a>
                    @else
                        <a href="{{ route('login') }}" class="text-synoria-ink-soft hover:text-synoria-ink">Connexion</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-synoria-green px-3 py-1.5 font-medium text-white hover:bg-synoria-green-dark">Inscription</a>
                    @endauth
                </nav>
            </header>

            <main class="relative mx-auto max-w-5xl px-6 py-14 md:py-20">
                <div class="absolute inset-x-4 top-8 bottom-8 -z-0 rounded-[2rem] synoria-hero-glow opacity-90 pointer-events-none" aria-hidden="true"></div>

                <div class="relative grid gap-12 md:grid-cols-[1.1fr_0.9fr] md:items-center">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-synoria-green">Suite Synoria</p>
                        <h1 class="mt-3 max-w-xl text-4xl font-bold tracking-tight text-synoria-ink md:text-5xl">
                            SynoriaEats
                        </h1>
                        <p class="mt-3 max-w-xl text-xl text-synoria-ink-soft">
                            Commande tes repas, on s’occupe de la livraison.
                        </p>
                        <p class="mt-5 max-w-xl text-base text-synoria-ink-soft">
                            Restos près de chez toi, menu clair, compte sécurisé: la livraison de la suite Synoria.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('restaurants.index') }}" class="inline-flex items-center rounded-md bg-synoria-green px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-synoria-green-dark">
                                Explorer les restaurants
                            </a>
                            <a href="{{ route('register') }}" class="inline-flex items-center rounded-md border border-synoria-ink/10 bg-white/90 px-5 py-2.5 text-sm font-semibold text-synoria-ink hover:bg-synoria-yellow-soft">
                                Créer un compte
                            </a>
                        </div>
                    </div>

                    <div class="relative justify-self-center md:justify-self-end">
                        <div class="absolute -inset-10 rounded-full bg-synoria-yellow/55 blur-3xl" aria-hidden="true"></div>
                        <img
                            src="{{ asset('images/synoria-logo.png') }}"
                            alt="Logo Synoria"
                            class="relative w-56 sm:w-64 md:w-72 rounded-[2rem] shadow-synoria ring-1 ring-synoria-ink/10 object-cover"
                        >
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
