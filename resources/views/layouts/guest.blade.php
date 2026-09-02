<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SynoriaEats') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/synoria-icon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />

        <x-theme-init />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-synoria-ink antialiased dark:text-gray-100">
        <div class="synoria-shell flex flex-col sm:justify-center items-center pt-6 sm:pt-0 min-h-screen">
            <div class="absolute top-4 right-4 z-10">
                <x-ui-preferences />
            </div>
            <div class="px-4">
                <x-brand size="lg" />
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-5 synoria-panel sm:rounded-2xl shadow-synoria">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
