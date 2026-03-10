<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        // Verificar se temos os dados do usuário do middleware MicroserviceAuth
        if (!$request->attributes->has('user_data')) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }
        
        $userData = $request->attributes->get('user_data');
        
        // Verificar se o usuário tem a permissão necessária
        if (!isset($userData['permissions']) || !in_array($permission, $userData['permissions'])) {
            return response()->json([
                'error' => 'Permissão negada',
                'required_permission' => $permission
            ], 403);
        }
        
        return $next($request);
    }
}