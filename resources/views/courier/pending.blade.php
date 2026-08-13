<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Accès livreur</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                <p class="text-gray-800 font-medium">Les livreurs ne s’inscrivent plus librement.</p>
                <p class="text-sm text-gray-600">
                    SynoriaEats travaille avec des partenaires de livraison. Ton compte doit être créé puis validé par un admin.
                </p>
                @if (auth()->user()->partner_name)
                    <p class="text-sm text-gray-600">Partenaire : <strong>{{ auth()->user()->partner_name }}</strong></p>
                @endif
                <p class="text-sm">
                    Statut :
                    <span class="font-medium text-amber-700">{{ auth()->user()->approval_status->label() }}</span>
                </p>
                @if (auth()->user()->approval_notes)
                    <p class="text-sm text-gray-600">{{ auth()->user()->approval_notes }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
