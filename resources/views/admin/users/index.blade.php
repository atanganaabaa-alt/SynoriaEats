<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-emerald-700 hover:underline">← Dashboard</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Comptes</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 sm:grid-cols-4">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, email, tél…"
                       class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:col-span-2">
                <select name="role" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Tous rôles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <select name="active" class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Actifs & suspendus</option>
                    <option value="1" @selected(request('active') === '1')>Actifs</option>
                    <option value="0" @selected(request('active') === '0')>Suspendus</option>
                </select>
                <x-primary-button class="sm:col-span-4 sm:w-fit">Filtrer</x-primary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Utilisateur</th>
                            <th class="px-4 py-3 font-medium">Rôle</th>
                            <th class="px-4 py-3 font-medium">Statut</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                    <p class="text-gray-500">{{ $user->email }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $user->role->label() }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $user->is_active ? 'text-emerald-700' : 'text-red-600' }}">
                                        {{ $user->is_active ? 'Actif' : 'Suspendu' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @unless ($user->id === auth()->id() || $user->isAdmin())
                                        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                            <button class="text-sm {{ $user->is_active ? 'text-red-600' : 'text-emerald-700' }} hover:underline">
                                                {{ $user->is_active ? 'Suspendre' : 'Réactiver' }}
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
