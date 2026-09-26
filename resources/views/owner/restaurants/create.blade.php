<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('owner.restaurants.index')" :label="__('Mes restaurants')" />
            <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">Créer un restaurant</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('owner.restaurants.store') }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @if ($errors->any())
                    <div class="bg-red-50 text-red-800 px-4 py-3 rounded-md text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('owner.restaurants._form')
                <x-primary-button>Enregistrer</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
