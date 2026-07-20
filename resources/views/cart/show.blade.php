<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mon panier</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            @if ($lines->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    Panier vide. <a href="{{ route('restaurants.index') }}" class="text-emerald-700 hover:underline">Voir les restaurants</a>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <ul class="divide-y divide-gray-100">
                        @foreach ($lines as $line)
                            <li class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $line['menu_item']->name }}</p>
                                    <p class="text-sm text-gray-500">{{ number_format($line['menu_item']->price, 0, ',', ' ') }} FCFA / unité</p>
                                </div>
                                <form method="POST" action="{{ route('cart.update', $line['menu_item']) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" min="0" max="20" value="{{ $line['quantity'] }}"
                                           class="w-20 rounded-md border-gray-300 text-sm">
                                    <button type="submit" class="text-sm text-emerald-700 hover:underline">Mettre à jour</button>
                                </form>
                                <p class="font-semibold text-emerald-700">{{ number_format($line['line_total'], 0, ',', ' ') }} FCFA</p>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 pt-4 border-t border-gray-100 space-y-1 text-sm">
                        <div class="flex justify-between"><span>Sous-total</span><span>{{ number_format($subtotal, 0, ',', ' ') }} FCFA</span></div>
                        <div class="flex justify-between"><span>Livraison</span><span>{{ number_format($deliveryFee, 0, ',', ' ') }} FCFA</span></div>
                        <div class="flex justify-between font-semibold text-base"><span>Total</span><span>{{ number_format($total, 0, ',', ' ') }} FCFA</span></div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('checkout.show') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-500">
                            Commander
                        </a>
                        <form method="POST" action="{{ route('cart.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-gray-500 hover:text-red-600">Vider le panier</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
