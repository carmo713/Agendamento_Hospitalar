<?php
// app/Http/Middleware/RoleMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Não autenticado');
        }

        if (
            ($role === 'admin' && !$user->isAdmin()) ||
            ($role === 'doctor' && !$user->isDoctor()) ||
            ($role === 'patient' && !$user->isPatient())
        ) {
            abort(403, 'Acesso negado');
        }

        return $next($request);
    }
}
