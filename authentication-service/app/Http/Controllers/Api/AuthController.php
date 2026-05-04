<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\LogAutenticacao;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'ativo' => true
        ];

        if (!$token = JWTAuth::attempt($credentials)) {
            // Registrar tentativa de login mal sucedida
            LogAutenticacao::create([
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'email' => $request->email,
                'tipo' => 'failed_attempt',
                'mensagem' => 'Credenciais inválidas'
            ]);

            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        // Buscar o usuário para usar nas informações de log
        $user = Auth::user();

        // Registrar login bem-sucedido
        LogAutenticacao::create([
            'usuario_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'email' => $user->email,
            'tipo' => 'login',
            'mensagem' => 'Login bem-sucedido'
        ]);

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            // O middleware jwt.verify já verificou o token, então Auth::user() deve estar disponível
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Não foi possível obter informações do usuário'], 401);
            }
            
            // Buscar usuário com relacionamentos
            $user = User::with('perfis.permissoes')->find($user->id);
            
            // Extrair permissões para uso em outros microserviços
            $permissions = [];
            $roles = [];
            
            foreach($user->perfis as $perfil) {
                $roles[] = $perfil->codigo;
                foreach($perfil->permissoes as $permissao) {
                    $permissions[] = $permissao->codigo;
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao processar dados do usuário: '.$e->getMessage()], 500);
        }
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'cargo' => $user->cargo,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'ativo' => $user->ativo,
                'ultimo_login' => $user->ultimo_login,
            ],
            'roles' => $roles,
            'permissions' => array_unique($permissions)
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Verificar se o usuário está autenticado
            if (Auth::check()) {
                $user = Auth::user();
                
                // Registrar logout
                LogAutenticacao::create([
                    'usuario_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'email' => $user->email,
                    'tipo' => 'logout',
                    'mensagem' => 'Logout realizado'
                ]);
                
                Auth::logout();
            }
            
            return response()->json(['message' => 'Logout realizado com sucesso']);
        } catch (\Exception $e) {
            // Registrar o erro para depuração
            
            // Forçar logout mesmo em caso de erro
            Auth::logout();
            
            return response()->json(['message' => 'Logout realizado com sucesso']);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            return $this->respondWithToken($token);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Não foi possível atualizar o token'], 401);
        }
    }

    /**
     * Change user password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:current_password',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Verificando se a senha atual está correta - ajustando para o campo 'senha'
        if (!Auth::attempt(['email' => $user->email, 'password' => $request->current_password])) {
            return response()->json(['error' => 'Senha atual incorreta'], 401);
        }

        // Atualizando a senha - ajustando para o campo 'senha'
        $userModel = User::find($user->id);
        $userModel->senha = bcrypt($request->new_password);
        $userModel->save();

        // Registrando a alteração de senha
        LogAutenticacao::create([
            'usuario_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'email' => $user->email,
            'tipo' => 'password_change',
            'mensagem' => 'Senha alterada com sucesso'
        ]);

        return response()->json(['message' => 'Senha alterada com sucesso']);
    }

    /**
     * Get auth logs for authenticated user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyAuthLogs(Request $request)
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        
        $limit = $request->get('limit', 20);
        
        $logs = LogAutenticacao::where('usuario_id', Auth::id())
                               ->orderBy('created_at', 'desc')
                               ->limit($limit)
                               ->get();
                               
        return response()->json($logs);
    }
    
    /**
     * Get system status
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function systemStatus()
    {
        return response()->json([
            'status' => 'online',
            'service' => 'authentication',
            'version' => '1.0',
            'server_time' => Carbon::now(),
            'environment' => config('app.env'),
            'total_users' => User::count(),
            'active_users' => User::where('ativo', true)->count()
        ]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = Auth::user();
        $permissions = [];
        
        // Buscar usuário com relacionamentos
        $userWithRoles = User::with('perfis.permissoes')->find($user->id);
        
        // Obter todas as permissões dos perfis do usuário
        foreach($userWithRoles->perfis as $perfil) {
            foreach($perfil->permissoes as $permissao) {
                $permissions[] = $permissao->codigo;
            }
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'cargo' => $user->cargo,
                'permissions' => array_unique($permissions)
            ]
        ]);
    }

    /**
     * Service-to-service authentication
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function serviceAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar credenciais do serviço
        $validServices = [
            'patient-service' => 'patient-service-secret',
            'triage-service' => 'triage-service-secret',
            'configuration-service' => 'configuration-service-secret',
            'laboratory-service' => 'laboratory-service-secret',
            'prescription-service' => 'prescription-service-secret',
            'appointment-service' => 'appointment-service-secret',
        ];

        $clientId = $request->client_id;
        $clientSecret = $request->client_secret;

        if (!isset($validServices[$clientId]) || $validServices[$clientId] !== $clientSecret) {
            return response()->json(['error' => 'Credenciais de serviço inválidas'], 401);
        }

        // Buscar um usuário admin existente para gerar o token ou criar um payload customizado
        try {
            // Criar payload customizado para serviço
            $payload = JWTAuth::getJWTProvider()->encode([
                'iss' => config('app.name'),
                'sub' => 'service:' . $clientId,
                'aud' => 'microservices',
                'iat' => time(),
                'exp' => time() + (config('jwt.ttl') * 60),
                'service' => $clientId,
                'type' => 'service_token'
            ]);
            
            return response()->json([
                'token' => $payload,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'service' => $clientId
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Não foi possível criar token para o serviço'], 500);
        }
    }
}