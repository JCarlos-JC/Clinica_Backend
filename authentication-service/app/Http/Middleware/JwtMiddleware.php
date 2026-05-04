<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Tentar localizar o token em vários lugares (header, server vars, query, body)
            $token = $request->bearerToken();
            if (!$token) {
                // Try common server variables where Authorization may be set
                $serverAuth = $request->server('HTTP_AUTHORIZATION') ?: $request->server('REDIRECT_HTTP_AUTHORIZATION');
                if ($serverAuth) {
                    if (stripos($serverAuth, 'bearer ') === 0) {
                        $token = trim(substr($serverAuth, 7));
                    } else {
                        $token = $serverAuth;
                    }
                }
            }

            // Fallback to token param in body or query (only for debugging/testing)
            if (!$token) {
                $token = $request->input('token') ?: $request->query('token');
            }

            if (!$token) {
                Log::warning('JwtMiddleware: token não encontrado em header/server/query/body');
                return response()->json(['error' => 'Token não fornecido no cabeçalho Authorization'], 401);
            }

            // Set token explicitly and authenticate
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();
            if (!$user) {
                return response()->json(['error' => 'Usuário não encontrado'], 401);
            }
            
            // Verificar se o usuário está ativo
            if (!$user->ativo) {
                return response()->json(['error' => 'Conta de usuário desativada'], 403);
            }
            
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expirado'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token inválido'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token inválido ou mal formatado'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro de autenticação', 'message' => $e->getMessage()], 500);
        }
        
        return $next($request);
    }
}
