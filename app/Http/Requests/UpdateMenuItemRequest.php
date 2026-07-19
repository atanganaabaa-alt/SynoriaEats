<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MenuItem $menuItem */
        $menuItem = $this->route('menuItem');

        return $this->user()?->isAdmin()
            || ($this->user()?->role === UserRole::RestaurantOwner
                && $menuItem->restaurant->owner_id === $this->user()->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:60'],
            'is_available' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
