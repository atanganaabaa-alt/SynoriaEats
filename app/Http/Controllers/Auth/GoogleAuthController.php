<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $request->session()->put('google_oauth_role', $request->string('role')->toString() ?: UserRole::Customer->value);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Connexion Google impossible. Réessaie ou utilise email / mot de passe.']);
        }

        $roleValue = $request->session()->pull('google_oauth_role', UserRole::Customer->value);
        $role = UserRole::tryFrom($roleValue) ?? UserRole::Customer;

        if ($role === UserRole::Admin) {
            $role = UserRole::Customer;
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'name' => $user->name ?: ($googleUser->getName() ?? $user->email),
            ])->save();
        } else {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?: ($googleUser->getEmail() ?? 'Utilisateur SynoriaEats'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role' => $role,
                'password' => null,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        }

        if (! $user->is_active) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Ce compte est suspendu. Contacte le support SynoriaEats.']);
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
