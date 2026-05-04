<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassificacaoRisco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassificacaoRiscoController extends Controller
{
    /**
     * Listar todas as classificações de risco
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = ClassificacaoRisco::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('nome', 'like', "%{$search}%");
            }
            
            $classificacoes = $query->orderBy('nivel_prioridade', 'desc')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $classificacoes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar classificações de risco: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter uma classificação de risco específica
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $classificacaoRisco = ClassificacaoRisco::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $classificacaoRisco
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Classificação de risco não encontrada'
            ], 404);
        }
    }
    
    /**
     * Criar uma nova classificação de risco
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:classificacao_risco',
            'codigo' => 'nullable|string|max:20|unique:classificacao_risco',
            'descricao' => 'nullable|string',
            'cor' => 'nullable|string|max:20',
            'tempo_atendimento_minutos' => 'nullable|integer|min:0',
            'nivel_prioridade' => 'required|integer|min:0',
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
            $classificacaoRisco = ClassificacaoRisco::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Classificação de risco criada com sucesso',
                'data' => $classificacaoRisco
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar classificação de risco: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar uma classificação de risco existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $classificacaoRisco = ClassificacaoRisco::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('classificacao_risco')->ignore($classificacaoRisco->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('classificacao_risco')->ignore($classificacaoRisco->id)
                ],
                'descricao' => 'nullable|string',
                'cor' => 'nullable|string|max:20',
                'tempo_atendimento_minutos' => 'nullable|integer|min:0',
                'nivel_prioridade' => 'sometimes|required|integer|min:0',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $classificacaoRisco->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Classificação de risco atualizada com sucesso',
                'data' => $classificacaoRisco
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar classificação de risco: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Excluir uma classificação de risco
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $classificacaoRisco = ClassificacaoRisco::findOrFail($id);
            $classificacaoRisco->ativo = false;
            $classificacaoRisco->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Classificação de risco desativada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar classificação de risco: ' . $e->getMessage()
            ], 500);
        }
    }
}