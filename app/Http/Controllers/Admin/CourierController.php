<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function index(Request $request): View
    {
        $couriers = User::query()
            ->where('role', UserRole::Courier)
            ->when($request->filled('status'), fn ($q) => $q->where('approval_status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.couriers.index', compact('couriers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'partner_name' => ['required', 'string', 'max:150'],
            'password' => ['required', Password::defaults()],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => UserRole::Courier,
            'partner_name' => $validated['partner_name'],
            'is_active' => true,
            'approval_status' => ApprovalStatus::Pending,
        ]);

        return back()->with('status', 'Livreur partenaire créé — valide-le pour qu’il puisse prendre des missions.');
    }

    public function update(Request $request, User $user, ApprovalService $approvals): RedirectResponse
    {
        abort_unless($user->isCourier(), 404);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['decision'] === 'approved') {
            $approvals->approveCourier($user, $validated['notes'] ?? null);
            $message = 'Livreur approuvé.';
        } else {
            $approvals->rejectCourier($user, $validated['notes'] ?: 'Dossier rejeté.');
            $message = 'Livreur rejeté.';
        }

        return back()->with('status', $message);
    }
}
