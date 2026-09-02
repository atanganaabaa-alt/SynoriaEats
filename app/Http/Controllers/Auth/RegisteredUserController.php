<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\CloudinaryUploader;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'roles' => [
                UserRole::Customer,
                UserRole::RestaurantOwner,
            ],
            'documentTypes' => DocumentType::cases(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, CloudinaryUploader $media): RedirectResponse
    {
        $isOwner = $request->input('role') === UserRole::RestaurantOwner->value;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in([
                UserRole::Customer->value,
                UserRole::RestaurantOwner->value,
            ])],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($isOwner) {
            $rules = array_merge($rules, [
                'restaurant_name' => ['required', 'string', 'max:150'],
                'restaurant_address' => ['required', 'string', 'max:255'],
                'commerce_register' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                'identity' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
                'proof_of_address' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            ]);
        }

        $validated = $request->validate($rules, [
            'email.unique' => 'Un compte existe déjà avec cet email. Connecte-toi plutôt.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'commerce_register.required' => 'Le registre de commerce est obligatoire.',
            'identity.required' => 'Une pièce d’identité est obligatoire.',
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => UserRole::from($validated['role']),
            'is_active' => true,
            'approval_status' => $isOwner ? ApprovalStatus::Pending : ApprovalStatus::Approved,
            'approved_at' => $isOwner ? null : now(),
        ]);

        if ($isOwner) {
            $restaurant = Restaurant::query()->create([
                'owner_id' => $user->id,
                'name' => $validated['restaurant_name'],
                'slug' => Str::slug($validated['restaurant_name']).'-'.Str::lower(Str::random(5)),
                'address' => $validated['restaurant_address'],
                'is_open' => false,
                'is_validated' => false,
                'status' => ApprovalStatus::Pending,
            ]);

            $this->storeDocument($media, $restaurant, DocumentType::Identity, $request->file('identity'));

            if ($request->hasFile('commerce_register')) {
                $this->storeDocument($media, $restaurant, DocumentType::CommerceRegister, $request->file('commerce_register'));
            }

            if ($request->hasFile('proof_of_address')) {
                $this->storeDocument($media, $restaurant, DocumentType::ProofOfAddress, $request->file('proof_of_address'));
            }
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    private function storeDocument(CloudinaryUploader $media, Restaurant $restaurant, DocumentType $type, $file): void
    {
        $restaurant->documents()->create([
            'type' => $type,
            'url' => $media->upload($file, 'restaurant-docs'),
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
}
