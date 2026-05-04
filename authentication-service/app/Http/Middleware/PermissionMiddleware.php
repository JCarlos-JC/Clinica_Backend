<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (Auth::guest()) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);

        if (! Auth::user()->hasPermission($permissions)) {
            return response()->json(['error' => 'Acesso não autorizado. Permissões insuficientes.'], 403);
        }

        return $next($request);
    }
}