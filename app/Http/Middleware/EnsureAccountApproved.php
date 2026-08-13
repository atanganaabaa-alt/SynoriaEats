<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountApproved
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->needsApproval() && ! $user->isApproved()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'Compte en attente de validation admin.');
            }

            if ($user->isRestaurantOwner()) {
                return redirect()
                    ->route('owner.pending')
                    ->with('status', 'Ton compte restaurateur est en attente de validation admin.');
            }

            return redirect()
                ->route('courier.pending')
                ->with('status', 'Ton accès livreur n’est pas encore validé.');
        }

        return $next($request);
    }
}
