<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'SynoriaEats') }} — Livraison de repas</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased min-h-screen bg-gradient-to-br from-emerald-50 via-white to-lime-50 text-gray-900">
        <header class="mx-auto max-w-5xl px-6 pt-8 flex items-center justify-between">
            <p class="text-xl font-bold tracking-tight text-emerald-700">
                Synoria<span class="text-gray-900">Eats</span>
            </p>
            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('restaurants.index') }}" class="text-gray-600 hover:text-gray-900">Restaurants</a>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Connexion</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-emerald-600 px-3 py-1.5 font-medium text-white hover:bg-emerald-500">Inscription</a>
                @endauth
            </nav>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-20 md:py-28">
            <p class="text-sm font-medium uppercase tracking-wider text-emerald-700">Suite Synoria</p>
            <h1 class="mt-3 max-w-2xl text-4xl font-bold tracking-tight text-gray-900 md:text-5xl">
                Commande tes repas. SynoriaEats s’occupe de la livraison.
            </h1>
            <p class="mt-5 max-w-xl text-lg text-gray-600">
                L’app livraison de la suite Synoria — restos près de chez toi, menu clair, compte sécurisé.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('restaurants.index') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                    Explorer les restaurants
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Créer un compte
                </a>
            </div>
        </main>
    </body>
</html>
