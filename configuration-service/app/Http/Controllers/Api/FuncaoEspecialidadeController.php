<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuncaoEspecialidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FuncaoEspecialidadeController extends Controller
{
    /**
     * Listar todas as funções de especialidade
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = FuncaoEspecialidade::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('pode_prescrever')) {
                $query->where('pode_prescrever', $request->boolean('pode_prescrever'));
            }
            
            if ($request->has('pode_solicitar_exames')) {
                $query->where('pode_solicitar_exames', $request->boolean('pode_solicitar_exames'));
            }
            
            if ($request->has('pode_criar_prontuario')) {
                $query->where('pode_criar_prontuario', $request->boolean('pode_criar_prontuario'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            }
            
            $funcoes = $query->orderBy('nome')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $funcoes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar funções de especialidade: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter uma função de especialidade específica
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $funcaoEspecialidade = FuncaoEspecialidade::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $funcaoEspecialidade
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Função de especialidade não encontrada'
            ], 404);
        }
    }
    
    /**
     * Criar uma nova função de especialidade
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:funcao_especialidades',
            'codigo' => 'nullable|string|max:20|unique:funcao_especialidades',
            'descricao' => 'nullable|string',
            'pode_prescrever' => 'boolean',
            'pode_solicitar_exames' => 'boolean',
            'pode_criar_prontuario' => 'boolean',
            'ativo' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $funcaoEspecialidade = FuncaoEspecialidade::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Função de especialidade criada com sucesso',
                'data' => $funcaoEspecialidade
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar função de especialidade: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar uma função de especialidade existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $funcaoEspecialidade = FuncaoEspecialidade::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('funcao_especialidades')->ignore($funcaoEspecialidade->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('funcao_especialidades')->ignore($funcaoEspecialidade->id)
                ],
                'descricao' => 'nullable|string',
                'pode_prescrever' => 'boolean',
                'pode_solicitar_exames' => 'boolean',
                'pode_criar_prontuario' => 'boolean',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $funcaoEspecialidade->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Função de especialidade atualizada com sucesso',
                'data' => $funcaoEspecialidade
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar função de especialidade: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Desativar uma função de especialidade
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $funcaoEspecialidade = FuncaoEspecialidade::findOrFail($id);
            $funcaoEspecialidade->ativo = false;
            $funcaoEspecialidade->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Função de especialidade desativada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar função de especialidade: ' . $e->getMessage()
            ], 500);
        }
    }
}