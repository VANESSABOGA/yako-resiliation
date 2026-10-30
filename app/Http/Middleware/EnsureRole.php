<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage : ->middleware('role:agent,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte est désactivé.']);
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Accès non autorisé pour votre rôle.');
        }

        return $next($request);
    }
}
