<?php
// Em app/Http/Middleware/CheckRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!$request->user() || !$request->user()->hasRole($role)) {
            return redirect()->route('dashboard'); // Redireciona para um local padrão
        }

        return $next($request);
    }
}