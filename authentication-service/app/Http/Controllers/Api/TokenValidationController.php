<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;

class TokenValidationController extends Controller
{
    protected $authService;
    
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    /**
     * Validar um token JWT recebido de outro microserviço
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request)
    {
        // Obter o token do cabeçalho ou do corpo da requisição
        $token = $request->bearerToken() ?: $request->input('token');
        
        if (!$token) {
            return response()->json([
                'valid' => false,
                'error' => 'Token não fornecido'
            ], 400);
        }
        
        $result = $this->authService->validateToken($token);
        
        if (!$result) {
            return response()->json([
                'valid' => false,
                'error' => 'Token inválido ou expirado'
            ], 401);
        }
        
        return response()->json([
            'valid' => true,
            'data' => $result
        ]);
    }
}