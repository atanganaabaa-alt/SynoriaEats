<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::RestaurantOwner
            || $this->user()?->role === UserRole::Admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'prep_time_min' => ['nullable', 'integer', 'min:5', 'max:180'],
            'prep_time_max' => ['nullable', 'integer', 'min:5', 'max:240'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_open' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function validatedRestaurant(): array
    {
        $data = $this->safe()->except(['logo', 'cover']);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['owner_id'] = $this->user()->id;
        // Un resto non validé ne doit jamais être ouvert au catalogue public.
        $approved = $this->user()?->isAdmin() ?? false;
        $data['is_validated'] = $approved;
        $data['status'] = $approved
            ? \App\Enums\ApprovalStatus::Approved
            : \App\Enums\ApprovalStatus::Pending;
        $data['is_open'] = $approved ? $this->boolean('is_open', false) : false;
        $data['prep_time_min'] = $data['prep_time_min'] ?? 20;
        $data['prep_time_max'] = $data['prep_time_max'] ?? 40;
        $data['delivery_fee'] = $data['delivery_fee'] ?? 0;

        return $data;
    }
}
