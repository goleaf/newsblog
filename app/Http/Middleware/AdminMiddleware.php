<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * This middleware is used for deprecated admin routes that redirect to Nova.
     * Nova handles its own authentication, but we still need to check authorization
     * for the redirect routes to ensure only authorized users can access them.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use Nova's guard if configured, otherwise use default web guard
        $guard = config('nova.guard', 'web');

        if (! auth($guard)->check()) {
            return redirect()->route('login')->with('error', 'Please login to access admin area.');
        }

        $user = auth($guard)->user();

        // Check if user has admin role (Nova will handle its own authorization)
        // This ensures only authorized users can access the redirect routes
        if (! in_array($user->role ?? null, ['admin', 'editor', 'author'])) {
            abort(403, 'Unauthorized access to admin area.');
        }

        return $next($request);
    }
}
