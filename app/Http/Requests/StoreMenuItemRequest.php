<?php

namespace App\Http\Requests;

use App\Enums\MenuCategory;
use App\Models\Restaurant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Restaurant|null $restaurant */
        $restaurant = $this->route('restaurant');

        if (! $restaurant || ! $this->user()) {
            return false;
        }

        return $this->user()->isAdmin()
            || ($this->user()->isRestaurantOwner() && $restaurant->owner_id === $this->user()->id);
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
            'category' => ['required', Rule::in(MenuCategory::values())],
            'is_available' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Seuls les restaurateurs peuvent gérer le menu de ce restaurant.');
    }

    /**
     * @return array<string, mixed>
     */
    public function menuItemAttributes(Restaurant $restaurant): array
    {
        return [
            'restaurant_id' => $restaurant->id,
            'name' => $this->string('name')->toString(),
            'description' => $this->input('description'),
            'price' => (int) $this->input('price'),
            'category' => $this->input('category', MenuCategory::Plats->value),
            'is_available' => $this->boolean('is_available', true),
        ];
    }
}
