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
        if (! $this->googleIsConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Google n’est pas encore configuré. Ajoute GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET dans ton .env, ou connecte-toi avec email / mot de passe.',
                ]);
        }

        $request->session()->put(
            'google_oauth_role',
            $request->string('role')->toString() ?: UserRole::Customer->value
        );

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->googleIsConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google n’est pas configuré sur ce serveur.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Connexion Google impossible. Réessaie ou utilise email / mot de passe.']);
        }

        if (blank($googleUser->getEmail())) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google n’a pas renvoyé d’email. Autorise l’accès à l’email ou utilise un autre compte.']);
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
                'name' => $googleUser->getName() ?: $googleUser->getEmail(),
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

    private function googleIsConfigured(): bool
    {
        $clientId = (string) config('services.google.client_id');
        $clientSecret = (string) config('services.google.client_secret');

        if (blank($clientId) || blank($clientSecret)) {
            return false;
        }

        return ! str_starts_with($clientId, 'COLLER')
            && ! str_contains($clientId, 'YOUR_')
            && ! str_starts_with($clientSecret, 'COLLER');
    }
}
