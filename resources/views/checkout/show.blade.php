<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <a href="{{ route('cart.show') }}" class="text-sm text-emerald-700 hover:underline">← Panier</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Finaliser la commande</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-2">
            <form method="POST" action="{{ route('checkout.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf

                <x-input-error :messages="$errors->get('checkout')" class="mb-2" />

                <div>
                    <x-input-label for="delivery_address" value="Adresse de livraison" />
                    <x-text-input id="delivery_address" name="delivery_address" class="block mt-1 w-full"
                                  :value="old('delivery_address')" required />
                    <x-input-error :messages="$errors->get('delivery_address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="delivery_phone" value="Téléphone" />
                    <x-text-input id="delivery_phone" name="delivery_phone" class="block mt-1 w-full"
                                  :value="old('delivery_phone', auth()->user()->phone)" required />
                    <x-input-error :messages="$errors->get('delivery_phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="payment_method" value="Paiement Mobile Money" />
                    <select id="payment_method" name="payment_method" required
                            class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="orange_money" @selected(old('payment_method') === 'orange_money')>Orange Money</option>
                        <option value="mtn_momo" @selected(old('payment_method', 'mtn_momo') === 'mtn_momo')>MTN MoMo</option>
                    </select>
                    <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="payment_phone" value="Numéro Mobile Money (optionnel)" />
                    <x-text-input id="payment_phone" name="payment_phone" class="block mt-1 w-full"
                                  :value="old('payment_phone')" placeholder="Même que livraison si vide" />
                </div>

                <div>
                    <x-input-label for="notes" value="Instructions (optionnel)" />
                    <textarea id="notes" name="notes" rows="2"
                              class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                </div>

                <x-primary-button class="w-full justify-center">Payer et confirmer</x-primary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 h-fit">
                <h3 class="font-semibold text-gray-900 mb-4">Récapitulatif</h3>
                <ul class="divide-y divide-gray-100 text-sm mb-4">
                    @foreach ($lines as $line)
                        <li class="py-2 flex justify-between gap-2">
                            <span>{{ $line['quantity'] }}× {{ $line['menu_item']->name }}</span>
                            <span>{{ number_format($line['line_total'], 0, ',', ' ') }} FCFA</span>
                        </li>
                    @endforeach
                </ul>
                <div class="space-y-1 text-sm border-t border-gray-100 pt-3">
                    <div class="flex justify-between"><span>Sous-total</span><span>{{ number_format($subtotal, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between"><span>Livraison</span><span>{{ number_format($deliveryFee, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between font-semibold text-base"><span>Total</span><span>{{ number_format($total, 0, ',', ' ') }} FCFA</span></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
