<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class PreventBruteForce
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'login:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 15;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
                'code'    => 'TOO_MANY_ATTEMPTS',
            ], 429);
        }

        $response = $next($request);

        // Kalau login gagal (401), tambah hit counter
        if ($response->getStatusCode() === 401) {
            RateLimiter::hit($key, $decayMinutes * 60);
        } else {
            // Login berhasil, reset counter
            RateLimiter::clear($key);
        }

        return $response;
    }
}