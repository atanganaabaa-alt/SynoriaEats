<x-guest-layout>
    <div class="mb-6 space-y-3">
        <p class="text-sm text-gray-600 text-center">Inscription rapide avec Google (choisis ton rôle ci-dessous, puis clique)</p>
        <div class="grid grid-cols-1 gap-2">
            <a href="{{ route('google.redirect', ['role' => 'customer']) }}"
               class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Google · Client
            </a>
            <a href="{{ route('google.redirect', ['role' => 'restaurant_owner']) }}"
               class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Google · Restaurateur
            </a>
            <a href="{{ route('google.redirect', ['role' => 'courier']) }}"
               class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Google · Livreur
            </a>
        </div>
        <p class="text-center text-xs text-gray-500">ou crée un compte avec email + mot de passe</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nom')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" :value="__('Téléphone')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="role" :value="__('Je suis')" />
            <select id="role" name="role" required class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', 'customer') === $role->value)>
                        {{ $role->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" href="{{ route('login') }}">
                {{ __('Déjà inscrit ?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Créer mon compte') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
