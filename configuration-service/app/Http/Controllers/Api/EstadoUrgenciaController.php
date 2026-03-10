<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoUrgencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EstadoUrgenciaController extends Controller
{
    /**
     * Listar todos os estados de urgência
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = EstadoUrgencia::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            }
            
            // Ordenar por nível de prioridade (maior para menor) e depois por nome
            $estadosUrgencia = $query->orderBy('nivel_prioridade', 'desc')
                                    ->orderBy('nome')
                                    ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $estadosUrgencia
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar estados de urgência: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter um estado de urgência específico
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $estadoUrgencia = EstadoUrgencia::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $estadoUrgencia
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Estado de urgência não encontrado'
            ], 404);
        }
    }
    
    /**
     * Criar um novo estado de urgência
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:estados_urgencia',
            'codigo' => 'nullable|string|max:20|unique:estados_urgencia',
            'descricao' => 'nullable|string',
            'cor' => 'nullable|string|max:20',
            'icone' => 'nullable|string|max:50',
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
            $estadoUrgencia = EstadoUrgencia::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Estado de urgência criado com sucesso',
                'data' => $estadoUrgencia
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar estado de urgência: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar um estado de urgência existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $estadoUrgencia = EstadoUrgencia::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('estados_urgencia')->ignore($estadoUrgencia->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('estados_urgencia')->ignore($estadoUrgencia->id)
                ],
                'descricao' => 'nullable|string',
                'cor' => 'nullable|string|max:20',
                'icone' => 'nullable|string|max:50',
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
            
            $estadoUrgencia->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Estado de urgência atualizado com sucesso',
                'data' => $estadoUrgencia
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar estado de urgência: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Desativar um estado de urgência
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $estadoUrgencia = EstadoUrgencia::findOrFail($id);
            $estadoUrgencia->ativo = false;
            $estadoUrgencia->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Estado de urgência desativado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar estado de urgência: ' . $e->getMessage()
            ], 500);
        }
    }
}