<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiGatewayAuth
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
        try {
            // Obter o token do cabeçalho Authorization
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'error' => 'Token não encontrado', 
                    'headers' => $request->headers->all()
                ], 401);
            }
            
            // Verificar token com o serviço de autenticação
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get('http://localhost:8001/api/auth/me');
            
            if ($response->successful()) {
                // Armazenar informações do usuário na requisição
                $userData = $response->json();
                $request->attributes->add(['user_data' => $userData]);
                
                return $next($request);
            }
            
            return response()->json([
                'error' => 'Token inválido ou expirado',
                'auth_service_response' => $response->json()
            ], 401);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao processar autenticação',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}