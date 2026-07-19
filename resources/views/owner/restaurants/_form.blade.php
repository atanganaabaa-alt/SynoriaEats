@php $r = $restaurant ?? null; @endphp

<div>
    <x-input-label for="name" value="Nom" />
    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $r->name ?? '')" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="address" value="Adresse" />
    <x-text-input id="address" name="address" class="block mt-1 w-full" :value="old('address', $r->address ?? '')" required />
    <x-input-error :messages="$errors->get('address')" class="mt-2" />
</div>

<div>
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $r->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="category" value="Catégorie" />
        <x-text-input id="category" name="category" class="block mt-1 w-full" :value="old('category', $r->category ?? '')" />
    </div>
    <div>
        <x-input-label for="opening_hours" value="Horaires" />
        <x-text-input id="opening_hours" name="opening_hours" class="block mt-1 w-full" :value="old('opening_hours', $r->opening_hours ?? '10:00-22:00')" />
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <x-input-label for="prep_time_min" value="Délai min (min)" />
        <x-text-input id="prep_time_min" type="number" name="prep_time_min" class="block mt-1 w-full" :value="old('prep_time_min', $r->prep_time_min ?? 20)" />
    </div>
    <div>
        <x-input-label for="prep_time_max" value="Délai max (min)" />
        <x-text-input id="prep_time_max" type="number" name="prep_time_max" class="block mt-1 w-full" :value="old('prep_time_max', $r->prep_time_max ?? 40)" />
    </div>
    <div>
        <x-input-label for="delivery_fee" value="Frais livraison (FCFA)" />
        <x-text-input id="delivery_fee" type="number" name="delivery_fee" class="block mt-1 w-full" :value="old('delivery_fee', $r->delivery_fee ?? 500)" />
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="logo" value="Logo" />
        <input id="logo" type="file" name="logo" accept="image/*" class="block mt-1 w-full text-sm" />
        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="cover" value="Photo de couverture" />
        <input id="cover" type="file" name="cover" accept="image/*" class="block mt-1 w-full text-sm" />
        <x-input-error :messages="$errors->get('cover')" class="mt-2" />
    </div>
</div>

<label class="inline-flex items-center gap-2">
    <input type="checkbox" name="is_open" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" @checked(old('is_open', $r->is_open ?? true))>
    <span class="text-sm text-gray-700">Restaurant ouvert</span>
</label>
