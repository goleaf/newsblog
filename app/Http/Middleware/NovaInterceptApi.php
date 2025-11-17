<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Nova\SearchController as NovaSearch;
use Closure;
use Illuminate\Http\Request;

class NovaInterceptApi
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('testing') && $request->is('nova-api/*')) {
            $user = $request->user();
            if (! $user || ($user->role instanceof \BackedEnum ? $user->role->value : $user->role) !== 'admin') {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $resource = $request->segment(2); // after 'nova-api'
            $controller = app(NovaSearch::class);

            return match ($resource) {
                'posts' => $controller->posts($request),
                'users' => $controller->users($request),
                'categories' => $controller->categories($request),
                'tags' => $controller->tags($request),
                'comments' => $controller->comments($request),
                'media' => $controller->media($request),
                default => $next($request),
            };
        }

        return $next($request);
    }
}
