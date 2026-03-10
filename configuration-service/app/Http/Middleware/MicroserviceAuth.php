<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MicroserviceAuth
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
            // Obter o token de múltiplas fontes possíveis
            $token = $this->extractToken($request);
            
            if (!$token) {
                return response()->json(['error' => 'Token não fornecido'], 401);
            }

            // Verificar se o token está em cache para evitar muitas chamadas ao auth-service
            $cacheKey = 'auth_token_' . md5($token);
            
            if (Cache::has($cacheKey)) {
                // Token já validado anteriormente
                $userData = Cache::get($cacheKey);
                $request->attributes->add(['user_data' => $userData]);
                $request->merge(['authenticated_user' => $userData['user']]);
                return $next($request);
            }
            
            // Verificar token com o serviço de autenticação
            $authServiceUrl = env('AUTH_SERVICE_URL', 'http://localhost:8001');
            
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get($authServiceUrl . '/api/auth/me');
            
            if ($response->successful()) {
                // Armazenar informações do usuário na requisição
                $userData = $response->json();
                $request->attributes->add(['user_data' => $userData]);
                $request->merge(['authenticated_user' => $userData['user']]);
                
                // Armazenar em cache por 5 minutos para reduzir tráfego entre serviços
                Cache::put($cacheKey, $userData, 300);
                
                return $next($request);
            }
            
            if ($response->status() === 401) {
                return response()->json([
                    'error' => 'Token inválido ou expirado'
                ], 401);
            }
            
            return response()->json([
                'error' => 'Erro ao validar token com o serviço de autenticação'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Erro ao validar token no microserviço: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erro ao processar autenticação',
                'message' => config('app.debug') ? $e->getMessage() : 'Erro interno'
            ], 500);
        }
    }
    
    /**
     * Extrair token de várias fontes possíveis
     */
    private function extractToken(Request $request): ?string
    {
        // Tentar bearer token do header
        $token = $request->bearerToken();
        
        if (!$token) {
            // Tentar server variables
            $serverAuth = $request->server('HTTP_AUTHORIZATION') ?: $request->server('REDIRECT_HTTP_AUTHORIZATION');
            if ($serverAuth) {
                if (stripos($serverAuth, 'bearer ') === 0) {
                    $token = trim(substr($serverAuth, 7));
                } else {
                    $token = $serverAuth;
                }
            }
        }
        
        // Fallback para query ou body (apenas para debug/testes)
        if (!$token) {
            $token = $request->input('token') ?: $request->query('token');
        }
        
        return $token;
    }
}