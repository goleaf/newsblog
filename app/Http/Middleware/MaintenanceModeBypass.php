<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeBypass
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $file = storage_path('framework/down');

        // Only check if maintenance mode is enabled
        if (! File::exists($file)) {
            return $next($request);
        }

        $downFile = json_decode(File::get($file), true);
        $allowedIps = $downFile['allowed'] ?? [];

        // If no IPs are whitelisted, proceed normally
        if (empty($allowedIps)) {
            return $next($request);
        }

        // Get the client IP address
        $clientIp = $request->ip();

        // Check if client IP is in the whitelist
        if (in_array($clientIp, $allowedIps, true)) {
            // Set bypass cookie that Laravel's maintenance middleware checks
            // The cookie name matches Laravel's maintenance bypass cookie format
            $secret = $downFile['secret'] ?? null;
            if ($secret) {
                // Set the cookie in the request so Laravel's maintenance middleware can check it
                $request->cookies->set('laravel_maintenance', $secret);
            }
        }

        return $next($request);
    }
}
