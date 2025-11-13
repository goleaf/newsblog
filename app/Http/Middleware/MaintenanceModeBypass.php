<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $secret = $downFile['secret'] ?? null;
        $retryAfter = $downFile['retry'] ?? 60;

        // Check if user should bypass maintenance mode
        $shouldBypass = false;

        // 1. Check if user is authenticated as admin
        if ($request->user() && $request->user()->isAdmin()) {
            $shouldBypass = true;
        }

        // 2. Check if IP is whitelisted
        if (! $shouldBypass && ! empty($allowedIps)) {
            $clientIp = $request->ip();
            if (in_array($clientIp, $allowedIps, true)) {
                $shouldBypass = true;
            }
        }

        // 3. Check if secret token is in URL or cookie
        if (! $shouldBypass && $secret) {
            // Check URL path for secret
            if ($request->path() === $secret) {
                // Set cookie and redirect to home
                return redirect('/')
                    ->cookie('laravel_maintenance', $secret, 43200); // 30 days
            }

            // Check cookie for secret
            if ($request->cookie('laravel_maintenance') === $secret) {
                $shouldBypass = true;
            }
        }

        // If should bypass, allow the request
        if ($shouldBypass) {
            return $next($request);
        }

        // Otherwise, throw maintenance mode exception with proper headers
        $message = $downFile['message'] ?? 'We are currently performing maintenance. Please check back soon.';

        throw new HttpException(
            503,
            $message,
            null,
            [
                'Retry-After' => $retryAfter,
                'Content-Type' => 'text/html; charset=UTF-8',
            ]
        );
    }
}
