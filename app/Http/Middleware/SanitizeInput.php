<?php 
namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
 
class SanitizeInput
{
    // Field yang tidak boleh di-strip (password, token, dll)
    private array $except = [
        'password', 'password_confirmation', 'current_password',
        'token', 'secret', '_token',
    ];
 
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        $clean = $this->sanitize($input);
        $request->merge($clean);
 
        return $next($request);
    }
 
    private function sanitize(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except)) {
                $result[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                $result[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                // Strip tags + encode entitas HTML
                $result[$key] = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
 ?>