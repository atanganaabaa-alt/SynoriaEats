<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

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
            'category' => ['nullable', 'string', 'max:60'],
            'is_available' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Tu ne peux pas modifier ce plat.');
    }
}
