<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class DiagnosticController extends Controller
{
    /**
     * Mostrar todos os headers e server vars recebidos para debug
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showHeaders(Request $request)
    {
        return response()->json([
            'headers' => $request->headers->all(),
            'server_vars' => [
                'HTTP_AUTHORIZATION' => $request->server('HTTP_AUTHORIZATION'),
                'REDIRECT_HTTP_AUTHORIZATION' => $request->server('REDIRECT_HTTP_AUTHORIZATION'),
                'PHP_AUTH_USER' => $request->server('PHP_AUTH_USER'),
                'PHP_AUTH_PW' => $request->server('PHP_AUTH_PW'),
            ],
            'bearer_token' => $request->bearerToken(),
            'input_token' => $request->input('token'),
            'query_token' => $request->query('token'),
        ]);
    }
    
    /**
     * Analisar o token recebido e retornar informações detalhadas para diagnóstico
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeToken(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nenhum token encontrado no cabeçalho Authorization',
                'headers' => $request->headers->all()
            ], 400);
        }
        
        try {
            // Validar o token
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            $isValid = true;
            $error = null;
            
            // Verificar se conseguimos obter o usuário
            $user = Auth::guard('api')->user();
            $hasUser = !is_null($user);
            
            return response()->json([
                'status' => 'success',
                'token_analysis' => [
                    'is_valid' => $isValid,
                    'user_found' => $hasUser,
                    'error' => $error,
                    'payload' => [
                        'sub' => $payload->get('sub'),
                        'exp' => date('Y-m-d H:i:s', $payload->get('exp')),
                        'iat' => date('Y-m-d H:i:s', $payload->get('iat')),
                        'custom_claims' => [
                            'nome' => $payload->get('nome'),
                            'email' => $payload->get('email'),
                            'cargo' => $payload->get('cargo'),
                            'roles' => $payload->get('roles'),
                            'permissions' => $payload->get('permissions')
                        ]
                    ]
                ],
                'user_details' => $hasUser ? [
                    'id' => $user->id,
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'cargo' => $user->cargo,
                    'ativo' => $user->ativo
                ] : null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao analisar o token: ' . $e->getMessage(),
                'token' => $token
            ], 400);
        }
    }
}