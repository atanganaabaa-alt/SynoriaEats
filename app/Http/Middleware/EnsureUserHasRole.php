<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => UserRole::from($role))
            ->all();

        if (! in_array($user->role, $allowed, true)) {
            abort(403, 'Accès réservé à ce rôle.');
        }

        return $next($request);
    }
}
