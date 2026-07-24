<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier {{ $menuItem->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('owner.menu-items.update', $menuItem) }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                @include('owner.menu-items._form', ['menuItem' => $menuItem, 'categories' => $categories])
                <x-primary-button>Mettre à jour</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
