<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAutenticacao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $ativo = $request->get('ativo');
        
        $query = User::with('perfis');
        
        // Filtro de busca por nome ou email
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%");
            });
        }
        
        // Filtro por status ativo
        if ($ativo !== null) {
            $query->where('ativo', filter_var($ativo, FILTER_VALIDATE_BOOLEAN));
        }
        
        $users = $query->orderBy('created_at', 'desc')
                       ->paginate($perPage);
        
        return response()->json($users);
    }
    
    /**
     * Store a newly created user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'senha' => 'required|string|min:8',
            'cargo' => 'nullable|string|max:255',
            'perfis' => 'required|array',
            'perfis.*' => 'exists:perfis,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $user = User::create([
                'nome' => $request->nome,
                'email' => $request->email,
                'senha' => Hash::make($request->senha),
                'cargo' => $request->cargo,
                'ativo' => true,
                'criado_por' => Auth::id()
            ]);
            
            $user->perfis()->attach($request->perfis);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user->load('perfis')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao criar usuário', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified user
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with('perfis')->findOrFail($id);
        
        return response()->json($user);
    }

    /**
     * Update the specified user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:usuarios,email,'.$id,
            'senha' => 'sometimes|nullable|string|min:8',
            'cargo' => 'nullable|string|max:255',
            'ativo' => 'sometimes|boolean',
            'perfis' => 'sometimes|array',
            'perfis.*' => 'exists:perfis,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $user->nome = $request->nome ?? $user->nome;
            $user->email = $request->email ?? $user->email;
            $user->cargo = $request->cargo ?? $user->cargo;
            
            if ($request->has('ativo')) {
                $user->ativo = $request->ativo;
            }
            
            if ($request->filled('senha')) {
                $user->senha = Hash::make($request->senha);
            }
            
            $user->modificado_por = Auth::id();
            $user->save();
            
            if ($request->has('perfis')) {
                $user->perfis()->sync($request->perfis);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Usuário atualizado com sucesso',
                'user' => $user->load('perfis')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao atualizar usuário', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified user
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Você não pode excluir seu próprio usuário'], 403);
        }
        
        DB::beginTransaction();
        
        try {
            // Soft delete by deactivating the user
            $user->ativo = false;
            $user->modificado_por = Auth::id();
            $user->save();
            
            DB::commit();
            
            return response()->json(['message' => 'Usuário desativado com sucesso']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao desativar usuário', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get the authenticated user's auth logs
     *
     * @return \Illuminate\Http\Response
     */
    public function authLogs(Request $request)
    {
        $userId = $request->get('user_id', Auth::id());
        
        $limit = $request->get('limit', 50);
        
        $logs = LogAutenticacao::where('usuario_id', $userId)
                               ->orderBy('created_at', 'desc')
                               ->limit($limit)
                               ->get();
        
        return response()->json($logs);
    }

    /**
     * Get user roles
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getRoles($id)
    {
        $user = User::with('perfis')->findOrFail($id);
        
        return response()->json([
            'user_id' => $user->id,
            'nome' => $user->nome,
            'roles' => $user->perfis->map(function ($perfil) {
                return [
                    'id' => $perfil->id,
                    'nome' => $perfil->nome,
                    'descricao' => $perfil->descricao,
                ];
            })
        ]);
    }

    /**
     * Assign role to user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function assignRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:perfis,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = User::findOrFail($id);
        
        // Check if user already has this role
        if ($user->perfis()->where('perfil_id', $request->role_id)->exists()) {
            return response()->json([
                'message' => 'Usuário já possui este perfil'
            ], 400);
        }
        
        $user->perfis()->attach($request->role_id);
        
        return response()->json([
            'message' => 'Perfil atribuído com sucesso',
            'user' => $user->load('perfis')
        ]);
    }

    /**
     * Remove role from user
     *
     * @param  int  $id
     * @param  int  $roleId
     * @return \Illuminate\Http\Response
     */
    public function removeRole($id, $roleId)
    {
        $user = User::findOrFail($id);
        
        if (!$user->perfis()->where('perfil_id', $roleId)->exists()) {
            return response()->json([
                'message' => 'Usuário não possui este perfil'
            ], 400);
        }
        
        $user->perfis()->detach($roleId);
        
        return response()->json([
            'message' => 'Perfil removido com sucesso',
            'user' => $user->load('perfis')
        ]);
    }
}
