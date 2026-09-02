<x-guest-layout>
    <div class="mb-6 space-y-3">
        <a href="{{ route('google.redirect', ['role' => 'customer']) }}"
           class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-slate-800 dark:border-slate-600 dark:text-gray-100">
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#EA4335" d="M12 10.2v3.6h5.1c-.2 1.2-.9 2.3-1.9 3l3.1 2.4c1.8-1.7 2.9-4.1 2.9-7 0-.7-.1-1.3-.2-1.9H12z"/>
                <path fill="#34A853" d="M5.3 14.3l-.8.6-2.6 2C3.4 20.1 7.4 22.5 12 22.5c2.7 0 5-.9 6.7-2.4l-3.1-2.4c-.9.6-2 .9-3.6.9-2.8 0-5.1-1.9-5.9-4.4z"/>
                <path fill="#4A90E2" d="M3.9 7.1C3.3 8.3 3 9.6 3 11s.3 2.7.9 3.9c0 .1 4.1-3.2 4.1-3.2C7.6 9.9 9.6 8.4 12 8.4c1.3 0 2.5.5 3.4 1.2l2.6-2.6C16.4 5.5 14.3 4.5 12 4.5 7.4 4.5 3.4 6.9 3.9 7.1z"/>
                <path fill="#FBBC05" d="M12 8.4c1.3 0 2.5.5 3.4 1.2l2.6-2.6C16.4 5.5 14.3 4.5 12 4.5 7.4 4.5 3.4 6.9 1.9 9.1l3.4 2.6C6.9 9.3 9.2 8.4 12 8.4z"/>
            </svg>
            {{ __('Continuer avec Google') }}
        </a>
        <p class="text-center text-xs text-gray-500 dark:text-gray-400">{{ __('Restaurateur ?') }} <a href="{{ route('google.redirect', ['role' => 'restaurant_owner']) }}" class="text-synoria-green hover:underline">{{ __('S’inscrire avec Google') }}</a></p>
        <p class="text-center text-xs text-gray-500 dark:text-gray-400">{{ __('Livreur : pas d’inscription libre. L’admin t’invite via un partenariat, puis tu te connectes.') }}</p>
        <div class="relative my-2">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200 dark:border-slate-700"></div>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="bg-white px-2 text-gray-500 dark:bg-slate-900 dark:text-gray-400">{{ __('ou avec ton email') }}</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" x-data="{ role: '{{ old('role', 'customer') }}' }">
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
            <select id="role" name="role" required x-model="role" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', 'customer') === $role->value)>
                        {{ $role->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div class="mt-6 space-y-4 rounded-md border border-amber-200 bg-amber-50 p-4" x-show="role === 'restaurant_owner'" x-cloak>
            <p class="text-sm font-medium text-amber-900">{{ __('Preuves de fiabilité') }}</p>
            <p class="text-xs text-amber-800">{{ __('Ton restaurant restera invisible tant qu’un admin n’a pas approuvé le dossier.') }}</p>

            <div>
                <x-input-label for="restaurant_name" :value="__('Nom du restaurant')" />
                <x-text-input id="restaurant_name" class="block mt-1 w-full" type="text" name="restaurant_name" :value="old('restaurant_name')" />
                <x-input-error :messages="$errors->get('restaurant_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="restaurant_address" :value="__('Adresse du local')" />
                <x-text-input id="restaurant_address" class="block mt-1 w-full" type="text" name="restaurant_address" :value="old('restaurant_address')" />
                <x-input-error :messages="$errors->get('restaurant_address')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="commerce_register" :value="__('Registre de commerce / RCCM (optionnel)')" />
                <p class="text-xs text-amber-800 mt-0.5">{{ __('Si tu n’as pas encore de RCCM, envoie au minimum ta pièce d’identité.') }}</p>
                <input id="commerce_register" name="commerce_register" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('commerce_register')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="identity" :value="__('Pièce d’identité')" />
                <input id="identity" name="identity" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('identity')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="proof_of_address" :value="__('Justificatif d’adresse (optionnel)')" />
                <input id="proof_of_address" name="proof_of_address" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('proof_of_address')" class="mt-2" />
            </div>
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
