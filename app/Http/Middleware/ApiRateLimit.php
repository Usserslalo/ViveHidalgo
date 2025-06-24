<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->getMaxAttempts($request);
        $decayMinutes = $this->getDecayMinutes($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = Auth::user();
        $identifier = $user ? $user->id : $request->ip();
        
        return sha1($identifier . '|' . $request->route()?->getDomain() . '|' . $request->ip());
    }

    /**
     * Get the maximum number of attempts for the given request.
     */
    protected function getMaxAttempts(Request $request): int
    {
        $route = $request->route();
        $path = $route ? $route->uri() : '';

        // Different limits for different endpoints
        if (str_contains($path, 'auth')) {
            return 5; // Login/register attempts
        }

        if (str_contains($path, 'search')) {
            return 30; // Search requests
        }

        if (str_contains($path, 'public')) {
            return 100; // Public endpoints
        }

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('admin')) {
                return 1000; // Admin users
            }
            if ($user->hasRole('provider')) {
                return 500; // Provider users
            }
            return 200; // Regular users
        }

        return 60; // Default for unauthenticated requests
    }

    /**
     * Get the number of minutes to decay the rate limit.
     */
    protected function getDecayMinutes(Request $request): int
    {
        $route = $request->route();
        $path = $route ? $route->uri() : '';

        if (str_contains($path, 'auth')) {
            return 15; // 15 minutes for auth endpoints
        }

        return 1; // 1 minute for other endpoints
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
            'max_attempts' => $maxAttempts
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        return $response->header('X-RateLimit-Limit', $maxAttempts)
                       ->header('X-RateLimit-Remaining', $remainingAttempts);
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - RateLimiter::attempts($key);
    }
} 