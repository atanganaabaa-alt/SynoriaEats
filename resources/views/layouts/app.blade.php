<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100" @auth x-data="liveToasts()" x-init="start()" @endauth>
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            @auth
                <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]" aria-live="polite">
                    <template x-for="toast in toasts" :key="toast.id">
                        <a :href="toast.url"
                           class="block rounded-lg bg-gray-900 text-white shadow-lg px-4 py-3 text-sm hover:bg-gray-800 transition"
                           @click="dismiss(toast.id)">
                            <p class="font-semibold" x-text="toast.title"></p>
                            <p class="text-gray-300 mt-0.5" x-text="toast.body"></p>
                        </a>
                    </template>
                </div>
            @endauth
        </div>

        @auth
            <script>
                function liveToasts() {
                    return {
                        toasts: [],
                        timer: null,
                        start() {
                            this.poll();
                            this.timer = setInterval(() => this.poll(), 4000);
                        },
                        async poll() {
                            try {
                                const res = await fetch(@json(route('notifications.poll')), {
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                    credentials: 'same-origin',
                                });
                                if (!res.ok) return;
                                const data = await res.json();
                                (data.notifications || []).forEach((n) => {
                                    this.toasts.unshift(n);
                                    setTimeout(() => this.dismiss(n.id), 8000);
                                });
                                this.toasts = this.toasts.slice(0, 5);
                            } catch (e) {}
                        },
                        dismiss(id) {
                            this.toasts = this.toasts.filter((t) => t.id !== id);
                        },
                    };
                }
            </script>
        @endauth
    </body>
</html>
