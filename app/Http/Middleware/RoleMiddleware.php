<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Convert enum to string value for comparison with route parameters
        $userRole = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        if (! in_array($userRole, $roles)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
