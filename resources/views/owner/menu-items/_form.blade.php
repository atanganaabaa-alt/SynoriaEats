@php $m = $menuItem ?? null; @endphp

<div>
    <x-input-label for="name" value="Nom du plat" />
    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $m->name ?? '')" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $m->description ?? '') }}</textarea>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="price" value="Prix (FCFA)" />
        <x-text-input id="price" type="number" name="price" class="block mt-1 w-full" :value="old('price', $m->price ?? '')" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="category" value="Catégorie" />
        <x-text-input id="category" name="category" class="block mt-1 w-full" :value="old('category', $m->category ?? 'Plats')" />
    </div>
</div>

<div>
    <x-input-label for="photo" value="Photo" />
    <input id="photo" type="file" name="photo" accept="image/*" class="block mt-1 w-full text-sm" />
    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
</div>

<label class="inline-flex items-center gap-2">
    <input type="checkbox" name="is_available" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" @checked(old('is_available', $m->is_available ?? true))>
    <span class="text-sm text-gray-700">Disponible</span>
</label>
