<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        if (!in_array($user->role, $roles, true)) {
            return response()->json(['success' => false, 'message' => 'Accès interdit'], 403);
        }

        return $next($request);
    }
}
