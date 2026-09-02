<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password', [
            'mailConfigured' => config('mail.default') !== 'log'
                && filled(config('mail.from.address')),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        $message = __('Un lien de réinitialisation a été envoyé si cet email existe dans nos comptes.');

        if (config('mail.default') === 'log' || ! filled(config('mail.from.address'))) {
            $message .= ' '.__('L’envoi d’emails n’est pas encore configuré sur ce serveur : contacte l’admin ou utilise la commande synoria:reset-password en SSH.');
        }

        return back()->with('status', $message);
    }
}
