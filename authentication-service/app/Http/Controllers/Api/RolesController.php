<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Perfil;
use App\Models\Permissao;

class RolesController extends Controller
{
    /**
     * Display a listing of roles
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Perfil::with('permissoes')->get();
        
        return response()->json($roles);
    }

    /**
     * Store a newly created role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:50|unique:perfis',
            'descricao' => 'nullable|string',
            'permissoes' => 'nullable|array',
            'permissoes.*' => 'exists:permissoes,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $role = Perfil::create([
                'nome' => $request->nome,
                'codigo' => $request->codigo,
                'descricao' => $request->descricao,
                'ativo' => true
            ]);
            
            if ($request->has('permissoes')) {
                $role->permissoes()->attach($request->permissoes);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Perfil criado com sucesso',
                'role' => $role->load('permissoes')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao criar perfil', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified role
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Perfil::with(['permissoes', 'usuarios'])->findOrFail($id);
        
        return response()->json($role);
    }

    /**
     * Update the specified role
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $role = Perfil::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'codigo' => 'sometimes|required|string|max:50|unique:perfis,codigo,'.$id,
            'descricao' => 'nullable|string',
            'ativo' => 'sometimes|boolean',
            'permissoes' => 'sometimes|array',
            'permissoes.*' => 'exists:permissoes,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $role->nome = $request->nome ?? $role->nome;
            $role->codigo = $request->codigo ?? $role->codigo;
            $role->descricao = $request->descricao ?? $role->descricao;
            
            if ($request->has('ativo')) {
                $role->ativo = $request->ativo;
            }
            
            $role->save();
            
            if ($request->has('permissoes')) {
                $role->permissoes()->sync($request->permissoes);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Perfil atualizado com sucesso',
                'role' => $role->load('permissoes')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao atualizar perfil', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified role
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = Perfil::findOrFail($id);
        
        // Check if role is in use
        if ($role->usuarios()->count() > 0) {
            return response()->json([
                'message' => 'Este perfil está sendo usado por usuários e não pode ser excluído'
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Detach permissions
            $role->permissoes()->detach();
            
            // Soft delete by deactivating
            $role->ativo = false;
            $role->save();
            
            DB::commit();
            
            return response()->json(['message' => 'Perfil desativado com sucesso']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao desativar perfil', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all permissions
     *
     * @return \Illuminate\Http\Response
     */
    public function permissions()
    {
        $permissions = Permissao::where('ativo', true)->get();
        
        return response()->json($permissions);
    }
}