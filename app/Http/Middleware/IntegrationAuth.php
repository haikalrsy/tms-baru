<?php
namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
 
class IntegrationAuth
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-Integration-Key')
            ?? $request->query('api_key');
 
        if ($key !== config('app.integration_api_key', env('INTEGRATION_API_KEY'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid integration API key.',
            ], 401);
        }
 
        return $next($request);
    }
} ?>
