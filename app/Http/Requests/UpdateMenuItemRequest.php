<?php

namespace App\Http\Requests;

use App\Enums\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MenuItem|null $menuItem */
        $menuItem = $this->route('menuItem');

        if (! $menuItem || ! $this->user()) {
            return false;
        }

        return $this->user()->isAdmin()
            || ($this->user()->isRestaurantOwner() && $menuItem->restaurant->owner_id === $this->user()->id);
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
        throw new AuthorizationException('Tu ne peux pas modifier ce plat.');
    }
}
