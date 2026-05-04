<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MicroserviceAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow service-to-service calls with a shared service token
        $serviceTokenHeader = $request->header('X-Service-Token') ?: $request->header('X_SERVICE_TOKEN');
        $configuredServiceToken = config('services.service_token');

        if ($serviceTokenHeader && $configuredServiceToken && hash_equals((string)$configuredServiceToken, (string)$serviceTokenHeader)) {
            // Mark request as coming from a trusted service
            $request->attributes->add(['user_data' => ['service' => true, 'service_token' => true]]);
            return $next($request);
        }

        // Extrair o token do request
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido no cabeçalho Authorization'
            ], 401);
        }

        // Verificar se o token está em cache
        $cacheKey = 'auth_token_' . md5($token);
        $userData = Cache::get($cacheKey);

        if (!$userData) {
            // Validar token com o serviço de autenticação
            try {
                $authServiceUrl = config('app.auth_service_url', env('AUTH_SERVICE_URL', 'http://localhost:8001'));
                
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])->get($authServiceUrl . '/api/auth/me');

                if (!$response->successful()) {
                    Log::warning('Token validation failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Token inválido ou expirado'
                    ], 401);
                }

                $userData = $response->json();

                // Cachear os dados do usuário por 5 minutos
                Cache::put($cacheKey, $userData, 300);

            } catch (\Exception $e) {
                Log::error('Error validating token with auth service', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao validar token com o serviço de autenticação'
                ], 500);
            }
        }

        // Adicionar dados do usuário ao request
        $request->attributes->add(['user_data' => $userData]);

        return $next($request);
    }

    /**
     * Extrai o token do request de múltiplas fontes possíveis
     */
    private function extractToken(Request $request): ?string
    {
        // 1. Tentar pelo método nativo do Laravel
        $token = $request->bearerToken();
        if ($token) {
            return $token;
        }

        // 2. Tentar pelas variáveis de servidor
        $authHeader = $request->server('HTTP_AUTHORIZATION');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 3. Tentar pelo REDIRECT_HTTP_AUTHORIZATION (alguns servidores)
        $authHeader = $request->server('REDIRECT_HTTP_AUTHORIZATION');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 4. Tentar pelos headers diretos
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 5. Tentar por query string (não recomendado, mas como fallback)
        $token = $request->query('token');
        if ($token) {
            return $token;
        }

        // 6. Tentar pelo body (para requests POST/PUT)
        $token = $request->input('token');
        if ($token) {
            return $token;
        }

        return null;
    }
}
