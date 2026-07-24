<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ajouter un plat — {{ $restaurant->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('owner.menu-items.store', $restaurant) }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
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
                @include('owner.menu-items._form', ['categories' => $categories ?? \App\Enums\MenuCategory::cases()])
                <x-primary-button>Ajouter</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
