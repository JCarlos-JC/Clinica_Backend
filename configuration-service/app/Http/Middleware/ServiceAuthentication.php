<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ServiceAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Verifica o token de serviço
        $token = $request->header('X-Service-Token');
        
        // Por segurança, em um ambiente real, você usaria um token seguro gerado
        // e armazenado de forma segura, possivelmente em variáveis de ambiente
        if ($token !== config('services.tokens.internal', 'service_token_default')) {
            return response()->json(['error' => 'Unauthorized. Invalid service token.'], 401);
        }

        return $next($request);
    }
}