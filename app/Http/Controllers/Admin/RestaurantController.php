<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $restaurants = Restaurant::query()
            ->with(['owner', 'documents'])
            ->withCount('menuItems')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('validated'), function ($query) use ($request) {
                $query->where('is_validated', $request->string('validated') === '1');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.restaurants.index', compact('restaurants'));
    }

    public function show(Restaurant $restaurant): View
    {
        $restaurant->load(['owner', 'documents']);

        $requiredTypes = [
            DocumentType::CommerceRegister,
            DocumentType::Identity,
        ];

        $presentTypes = $restaurant->documents->map(fn ($d) => $d->type)->all();
        $missingRequired = collect($requiredTypes)
            ->reject(fn (DocumentType $type) => $restaurant->documents->contains(fn ($d) => $d->type === $type))
            ->values();

        return view('admin.restaurants.show', [
            'restaurant' => $restaurant,
            'missingRequired' => $missingRequired,
            'hasRequiredDocs' => $missingRequired->isEmpty(),
            'presentTypes' => $presentTypes,
        ]);
    }

    public function update(Request $request, Restaurant $restaurant, ApprovalService $approvals): RedirectResponse
    {
        if ($request->has('is_open') && ! $request->has('decision') && ! $request->has('is_validated')) {
            $restaurant->update(['is_open' => $request->boolean('is_open')]);

            return back()->with('status', 'Restaurant mis à jour.');
        }

        if ($request->has('decision')) {
            $validated = $request->validate([
                'decision' => ['required', 'in:approved,rejected'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'checks' => ['nullable', 'array'],
                'checks.*' => ['string'],
            ]);

            if ($validated['decision'] === ApprovalStatus::Approved->value) {
                $checks = collect($validated['checks'] ?? []);
                $requiredChecks = ['docs_readable', 'identity_ok', 'address_ok', 'commerce_ok'];

                foreach ($requiredChecks as $check) {
                    if (! $checks->contains($check)) {
                        return back()
                            ->withInput()
                            ->withErrors([
                                'checks' => 'Coche les 4 points de vérification avant d’approuver le dossier.',
                            ]);
                    }
                }

                $types = $restaurant->documents()
                    ->pluck('type')
                    ->map(fn ($type) => $type instanceof DocumentType ? $type->value : (string) $type);

                $hasRequiredDocs = $types->contains(DocumentType::CommerceRegister->value)
                    && $types->contains(DocumentType::Identity->value);

                if (! $hasRequiredDocs) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'checks' => 'RCCM et pièce d’identité sont obligatoires avant approbation.',
                        ]);
                }

                $approvals->approveRestaurant($restaurant, $validated['notes'] ?? null);

                return back()->with('status', 'Restaurant approuvé. Le restaurateur peut maintenant gérer menu et cadre.');
            }

            $reason = trim((string) ($validated['notes'] ?? ''));
            if ($reason === '') {
                return back()
                    ->withInput()
                    ->withErrors(['notes' => 'Indique un motif de rejet clair pour le restaurateur.']);
            }

            $approvals->rejectRestaurant($restaurant, $reason);

            return back()->with('status', 'Restaurant rejeté.');
        }

        if ($request->has('is_validated')) {
            if ($request->boolean('is_validated')) {
                $approvals->approveRestaurant($restaurant);
            } else {
                $approvals->rejectRestaurant($restaurant, 'Validation retirée par l’admin.');
            }
        }

        return back()->with('status', 'Restaurant mis à jour.');
    }
}
