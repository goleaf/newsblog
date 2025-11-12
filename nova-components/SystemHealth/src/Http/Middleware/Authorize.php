<?php

namespace App\SystemHealth\Http\Middleware;

use App\SystemHealth\SystemHealth;

class Authorize
{
    /**
     * Handle the incoming request.
     */
    public function handle($request, $next)
    {
        $tool = new SystemHealth;

        return $tool->authorize($request) ? $next($request) : abort(403);
    }
}
