<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * SECURITY: Enforce authentication for API endpoints
     * For production: Use Laravel Sanctum or API tokens
     * For now: Use environment-based API key authentication
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from header or query parameter
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        // Get expected API key from environment
        $expectedApiKey = env('API_ACCESS_KEY');

        // If no API key is configured, allow access (backward compatibility)
        // IMPORTANT: Set API_ACCESS_KEY in .env for production!
        if (empty($expectedApiKey)) {
            \Log::warning('API_ACCESS_KEY not configured - API endpoints are publicly accessible!');
            return $next($request);
        }

        // Validate API key
        if (empty($apiKey) || !hash_equals($expectedApiKey, $apiKey)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key',
            ], 401);
        }

        return $next($request);
    }
}
