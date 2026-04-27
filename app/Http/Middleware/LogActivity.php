<?php
namespace App\Http\Middleware;
 
use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
 
class LogActivity
{
    private array $logMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
 
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
 
        if (in_array($request->method(), $this->logMethods) && $request->user()) {
            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                ActivityLog::log(
                    $request->method() . ' ' . $request->path(),
                    null,
                    [],
                    [],
                    $request->user()->id
                );
            }
        }
 
        return $response;
    }
}?>