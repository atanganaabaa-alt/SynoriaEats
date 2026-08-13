<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dossier restaurateur</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            @forelse ($restaurants as $restaurant)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                    <p class="font-semibold text-gray-900">{{ $restaurant->name }}</p>
                    <p class="text-sm text-gray-500">{{ $restaurant->address }}</p>
                    <p class="text-sm">
                        Statut :
                        <span class="font-medium {{ $restaurant->status->value === 'approved' ? 'text-emerald-700' : ($restaurant->status->value === 'rejected' ? 'text-red-700' : 'text-amber-700') }}">
                            {{ $restaurant->status->label() }}
                        </span>
                    </p>
                    @if ($restaurant->rejection_reason)
                        <p class="text-sm text-red-700">Motif : {{ $restaurant->rejection_reason }}</p>
                    @endif
                    <ul class="text-sm text-gray-600 list-disc ps-5">
                        @foreach ($restaurant->documents as $document)
                            <li>
                                {{ $document->type->label() }}
                                @if ($document->original_name)
                                    ({{ $document->original_name }})
                                @endif
                                —
                                <a href="{{ $document->publicUrl() }}" class="text-emerald-700 underline" target="_blank" rel="noopener">voir</a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-sm text-gray-500">Tu ne peux pas publier le menu tant que l’admin n’a pas approuvé.</p>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-600">Aucun dossier. Complète l’onboarding.</p>
                    <a href="{{ route('owner.onboarding') }}" class="mt-3 inline-flex text-sm text-emerald-700 underline">Déposer mes justificatifs</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
