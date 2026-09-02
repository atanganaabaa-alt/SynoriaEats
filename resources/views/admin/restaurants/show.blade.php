<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.restaurants.index') }}" class="text-sm text-emerald-700 hover:underline">← Restaurants</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $restaurant->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 text-red-800 px-4 py-3 rounded-md text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2">
                <p class="text-sm text-gray-500">{{ $restaurant->address }}</p>
                <p class="text-sm">Propriétaire : <strong>{{ $restaurant->owner->name }}</strong> ({{ $restaurant->owner->email }}@if($restaurant->owner->phone) · {{ $restaurant->owner->phone }}@endif)</p>
                <p class="text-sm">
                    Statut resto :
                    <strong class="{{ $restaurant->status->value === 'approved' ? 'text-emerald-700' : ($restaurant->status->value === 'rejected' ? 'text-red-700' : 'text-amber-700') }}">
                        {{ $restaurant->status->label() }}
                    </strong>
                    · Compte propriétaire :
                    <strong>{{ $restaurant->owner->approval_status?->label() ?? '—' }}</strong>
                </p>
                @if ($restaurant->rejection_reason)
                    <p class="text-sm text-red-700">Motif : {{ $restaurant->rejection_reason }}</p>
                @endif
                @if (! $hasRequiredDocs)
                    <p class="text-sm text-amber-700">
                        Dossier incomplet, manquent:
                        {{ $missingRequired->map(fn ($t) => $t->label())->implode(', ') }}
                    </p>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div>
                    <h3 class="font-medium text-gray-900">Justificatifs à vérifier</h3>
                    <p class="text-sm text-gray-500 mt-1">Ouvre chaque document, vérifie qu’il est lisible et cohérent avec le restaurant, puis coche la checklist ci-dessous.</p>
                </div>

                <div class="space-y-4">
                    @forelse ($restaurant->documents as $document)
                        <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $document->type->label() }}</p>
                                    <p class="text-xs text-gray-500">{{ $document->original_name ?: 'Fichier' }}</p>
                                </div>
                                <a href="{{ route('admin.documents.show', $document) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="inline-flex items-center px-3 py-1.5 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-500">
                                    Ouvrir / télécharger
                                </a>
                            </div>

                            @if ($document->isImage())
                                <img src="{{ route('admin.documents.show', $document) }}"
                                     alt="{{ $document->type->label() }}"
                                     class="max-h-64 w-auto rounded border border-gray-100 object-contain bg-gray-50">
                            @elseif ($document->isPdf())
                                <iframe
                                    src="{{ route('admin.documents.show', $document) }}"
                                    class="w-full h-80 rounded border border-gray-200 bg-gray-50"
                                    title="{{ $document->type->label() }}"
                                ></iframe>
                            @else
                                <p class="text-sm text-gray-500">Aperçu non disponible: ouvre le fichier dans un nouvel onglet.</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">Aucun document déposé.</p>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('admin.restaurants.update', $restaurant) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <h3 class="font-medium text-gray-900 mb-2">Checklist de vérification</h3>
                    <div class="space-y-2 text-sm text-gray-700">
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="checks[]" value="docs_readable" class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" @checked(collect(old('checks', []))->contains('docs_readable'))>
                            <span>Les documents s’ouvrent et sont lisibles</span>
                        </label>
                        @if ($restaurant->documents->contains(fn ($d) => $d->type === \App\Enums\DocumentType::CommerceRegister))
                            <label class="flex items-start gap-2">
                                <input type="checkbox" name="checks[]" value="commerce_ok" class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" @checked(collect(old('checks', []))->contains('commerce_ok'))>
                                <span>Le registre de commerce / RCCM correspond au restaurant « {{ $restaurant->name }} »</span>
                            </label>
                        @endif
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="checks[]" value="identity_ok" class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" @checked(collect(old('checks', []))->contains('identity_ok'))>
                            <span>La pièce d’identité correspond au propriétaire ({{ $restaurant->owner->name }})</span>
                        </label>
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="checks[]" value="address_ok" class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" @checked(collect(old('checks', []))->contains('address_ok'))>
                            <span>L’adresse déclarée ({{ $restaurant->address }}) est crédible / cohérente avec le justificatif</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('checks')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" value="Note / motif (obligatoire en cas de rejet)" />
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <button name="decision" value="approved" class="px-4 py-2 rounded-md bg-emerald-600 text-white text-sm {{ $hasRequiredDocs ? '' : 'opacity-60' }}">
                        Approuver
                    </button>
                    <button name="decision" value="rejected" class="px-4 py-2 rounded-md bg-red-600 text-white text-sm">
                        Rejeter
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
