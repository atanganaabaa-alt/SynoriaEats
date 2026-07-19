<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier {{ $restaurant->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('owner.restaurants.update', $restaurant) }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                @include('owner.restaurants._form', ['restaurant' => $restaurant])
                <x-primary-button>Mettre à jour</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
