<?php

namespace App\Http\Controllers\Owner;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\CloudinaryUploader;
use App\Services\RestaurantGeocoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private RestaurantGeocoder $geocoder) {}

    public function pending(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isApproved() || $user->ownedRestaurants()->where('status', ApprovalStatus::Approved)->exists()) {
            return redirect()
                ->route('owner.restaurants.index')
                ->with('status', 'Ton restaurant est approuvé. Ajoute ton menu, puis ouvre-le pour apparaître au catalogue clients.');
        }

        $restaurants = $user
            ->ownedRestaurants()
            ->with('documents')
            ->latest()
            ->get();

        return view('owner.pending', compact('restaurants'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->ownedRestaurants()->exists()) {
            return redirect()->route('owner.pending');
        }

        return view('owner.onboarding', [
            'documentTypes' => DocumentType::cases(),
        ]);
    }

    public function store(Request $request, CloudinaryUploader $media): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:150'],
            'restaurant_address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'commerce_register' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'identity' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'proof_of_address' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $data = [
            'address' => $validated['restaurant_address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ];
        $data = $this->geocoder->applyToRestaurantData($data);

        $restaurant = Restaurant::query()->create([
            'owner_id' => $request->user()->id,
            'name' => $validated['restaurant_name'],
            'slug' => Str::slug($validated['restaurant_name']).'-'.Str::lower(Str::random(5)),
            'address' => $validated['restaurant_address'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_open' => false,
            'is_validated' => false,
            'status' => ApprovalStatus::Pending,
        ]);

        foreach ([
            'commerce_register' => DocumentType::CommerceRegister,
            'identity' => DocumentType::Identity,
            'proof_of_address' => DocumentType::ProofOfAddress,
        ] as $field => $type) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $restaurant->documents()->create([
                    'type' => $type,
                    'url' => $media->upload($file, 'restaurant-docs'),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        $request->user()->update([
            'approval_status' => ApprovalStatus::Pending,
        ]);

        $status = 'Dossier envoyé. Un admin SynoriaEats va le vérifier.';
        if ($restaurant->latitude && $restaurant->longitude) {
            $status .= ' Position carte enregistrée.';
        }

        return redirect()
            ->route('owner.pending')
            ->with('status', $status);
    }
}
