<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class JwtAuthentication
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
            // Verificar o token JWT
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                Log::warning('JWT Auth: Token válido mas usuário não encontrado');
                return response()->json(['error' => 'Usuário não encontrado'], 401);
            }

            // Verificar se o usuário está ativo
            if (!$user->ativo) {
                Log::warning('JWT Auth: Tentativa de acesso com usuário inativo: ' . $user->email);
                return response()->json(['error' => 'Conta de usuário desativada'], 403);
            }
            
        } catch (TokenExpiredException $e) {
            Log::warning('JWT Auth: Token expirado');
            return response()->json(['error' => 'Token expirado', 'code' => 'token_expired'], 401);
        } catch (TokenInvalidException $e) {
            Log::warning('JWT Auth: Token inválido');
            return response()->json(['error' => 'Token inválido', 'code' => 'token_invalid'], 401);
        } catch (JWTException $e) {
            Log::warning('JWT Auth: Token não encontrado ou inválido - ' . $e->getMessage());
            return response()->json(['error' => 'Token não encontrado ou inválido', 'code' => 'token_absent'], 401);
        }

        return $next($request);
    }
}