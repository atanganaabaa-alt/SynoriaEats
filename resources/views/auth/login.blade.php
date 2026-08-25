<x-guest-layout>
    @php
        $googleId = (string) config('services.google.client_id');
        $googleSecret = (string) config('services.google.client_secret');
        $googleReady = filled($googleId)
            && filled($googleSecret)
            && ! str_starts_with($googleId, 'COLLER')
            && ! str_contains($googleId, 'YOUR_');
    @endphp

    @if ($googleReady)
        <div class="mb-6">
            <a href="{{ route('google.redirect') }}"
               class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M12 10.2v3.6h5.1c-.2 1.2-.9 2.3-1.9 3l3.1 2.4c1.8-1.7 2.9-4.1 2.9-7 0-.7-.1-1.3-.2-1.9H12z"/>
                    <path fill="#34A853" d="M5.3 14.3l-.8.6-2.6 2C3.4 20.1 7.4 22.5 12 22.5c2.7 0 5-.9 6.7-2.4l-3.1-2.4c-.9.6-2 .9-3.6.9-2.8 0-5.1-1.9-5.9-4.4z"/>
                    <path fill="#4A90E2" d="M3.9 7.1C3.3 8.3 3 9.6 3 11s.3 2.7.9 3.9c0 .1 4.1-3.2 4.1-3.2C7.6 9.9 9.6 8.4 12 8.4c1.3 0 2.5.5 3.4 1.2l2.6-2.6C16.4 5.5 14.3 4.5 12 4.5 7.4 4.5 3.4 6.9 3.9 7.1z"/>
                    <path fill="#FBBC05" d="M12 8.4c1.3 0 2.5.5 3.4 1.2l2.6-2.6C16.4 5.5 14.3 4.5 12 4.5 7.4 4.5 3.4 6.9 1.9 9.1l3.4 2.6C6.9 9.3 9.2 8.4 12 8.4z"/>
                </svg>
                Continuer avec Google
            </a>
            <p class="mt-3 text-center text-xs text-gray-500">ou avec ton email et mot de passe</p>
        </div>
    @endif

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Se souvenir de moi') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" href="{{ route('password.request') }}">
                    {{ __('Mot de passe oublié ?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Connexion') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
