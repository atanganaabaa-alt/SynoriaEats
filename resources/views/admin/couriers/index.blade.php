<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 hover:underline">← Dashboard</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Livreurs partenaires</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.couriers.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 grid gap-4 sm:grid-cols-2">
                @csrf
                <p class="sm:col-span-2 text-sm text-gray-600">Créer un compte partenaire. Le livreur se connecte ensuite (email/mdp ou Google si l’email correspond). Il ne prend des missions qu’après validation.</p>
                <div>
                    <x-input-label for="name" value="Nom" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email')" required />
                </div>
                <div>
                    <x-input-label for="phone" value="Téléphone" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" />
                </div>
                <div>
                    <x-input-label for="partner_name" value="Partenaire (Glovo, Yango, flotte interne…)" />
                    <x-text-input id="partner_name" name="partner_name" class="mt-1 block w-full" :value="old('partner_name')" required />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="password" value="Mot de passe temporaire" />
                    <x-text-input id="password" type="password" name="password" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-primary-button>Créer le livreur</x-primary-button>
                </div>
            </form>

            <div class="space-y-3">
                @forelse ($couriers as $courier)
                    <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="font-semibold">{{ $courier->name }}</p>
                            <p class="text-sm text-gray-500">{{ $courier->email }} · {{ $courier->partner_name ?: '—' }}</p>
                            <p class="text-sm {{ $courier->approval_status->value === 'approved' ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $courier->approval_status->label() }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.couriers.update', $courier) }}" class="flex flex-col sm:flex-row gap-2">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="notes" placeholder="Note" class="rounded-md border-gray-300 text-sm">
                            <button name="decision" value="approved" class="px-3 py-1.5 text-sm rounded-md bg-emerald-600 text-white">Approuver</button>
                            <button name="decision" value="rejected" class="px-3 py-1.5 text-sm rounded-md bg-red-600 text-white">Rejeter</button>
                        </form>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">Aucun livreur partenaire.</div>
                @endforelse
            </div>
            <div>{{ $couriers->links() }}</div>
        </div>
    </div>
</x-app-layout>
