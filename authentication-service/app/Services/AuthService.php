<?php

namespace App\Services;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Validar um token JWT e retornar o usuário se for válido
     * 
     * @param string $token
     * @return array|false Array com dados do usuário ou false se o token for inválido
     */
    public function validateToken($token)
    {
        try {
            // Verificar o token
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            $userId = $payload->get('sub');
            
            // Buscar o usuário pelo ID
            $user = User::find($userId);
            
            if (!$user) {
                Log::warning('Token válido, mas usuário não encontrado: ID ' . $userId);
                return false;
            }
            
            if (!$user->ativo) {
                Log::warning('Usuário desativado tentando acessar: ' . $user->email);
                return false;
            }
            
            // Extrair permissões do usuário
            $permissions = [];
            $roles = [];
            
            foreach($user->perfis as $perfil) {
                $roles[] = $perfil->codigo;
                foreach($perfil->permissoes as $permissao) {
                    $permissions[] = $permissao->codigo;
                }
            }
            
            return [
                'user' => [
                    'id' => $user->id,
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'cargo' => $user->cargo,
                ],
                'roles' => $roles,
                'permissions' => array_unique($permissions)
            ];
            
        } catch (JWTException $e) {
            Log::error('Erro ao validar token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Criar um novo token para um usuário
     * 
     * @param User $user
     * @return string
     */
    public function createToken(User $user)
    {
        return JWTAuth::fromUser($user);
    }
    
    /**
     * Invalidar um token
     * 
     * @param string $token
     * @return bool
     */
    public function invalidateToken($token = null)
    {
        try {
            $token = $token ?: JWTAuth::getToken();
            JWTAuth::invalidate($token);
            return true;
        } catch (JWTException $e) {
            Log::error('Erro ao invalidar token: ' . $e->getMessage());
            return false;
        }
    }
}