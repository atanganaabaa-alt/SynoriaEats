<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Justificatifs restaurateur</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('owner.onboarding.store') }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                <p class="text-sm text-gray-600">Envoie au minimum une pièce d’identité. Le RCCM est optionnel si tu n’en as pas encore.</p>

                <div>
                    <x-input-label for="restaurant_name" value="Nom du restaurant" />
                    <x-text-input id="restaurant_name" class="block mt-1 w-full" type="text" name="restaurant_name" :value="old('restaurant_name')" required />
                    <x-input-error :messages="$errors->get('restaurant_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="restaurant_address" value="Adresse" />
                    <x-text-input id="restaurant_address" class="block mt-1 w-full" type="text" name="restaurant_address" :value="old('restaurant_address')" required placeholder="Ex. GP Melen, Yaoundé" />
                    <x-input-error :messages="$errors->get('restaurant_address')" class="mt-2" />
                    <div class="mt-3">
                        <x-restaurant-address-map
                            address-input-id="restaurant_address"
                            :lat="old('latitude')"
                            :lng="old('longitude')"
                        />
                    </div>
                </div>
                <div>
                    <x-input-label for="commerce_register" value="Registre de commerce / RCCM (optionnel)" />
                    <input id="commerce_register" name="commerce_register" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm">
                    <x-input-error :messages="$errors->get('commerce_register')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="identity" value="Pièce d’identité" />
                    <input id="identity" name="identity" type="file" required accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm">
                    <x-input-error :messages="$errors->get('identity')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="proof_of_address" value="Justificatif d’adresse (optionnel)" />
                    <input id="proof_of_address" name="proof_of_address" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm">
                </div>

                <x-primary-button>Envoyer le dossier</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
