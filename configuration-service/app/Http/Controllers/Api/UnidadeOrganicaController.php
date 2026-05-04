<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnidadeOrganica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UnidadeOrganicaController extends Controller
{
    /**
     * Listar todas as unidades orgânicas
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = UnidadeOrganica::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('sigla', 'like', "%{$search}%");
                });
            }
            
            $unidadesOrganicas = $query->orderBy('nome')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $unidadesOrganicas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar unidades orgânicas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter uma unidade orgânica específica
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $unidadeOrganica = UnidadeOrganica::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $unidadeOrganica
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unidade orgânica não encontrada'
            ], 404);
        }
    }
    
    /**
     * Criar uma nova unidade orgânica
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255|unique:unidades_organica',
            'sigla' => 'nullable|string|max:20|unique:unidades_organica',
            'descricao' => 'nullable|string',
            'tipo' => 'nullable|string|max:50',
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
            $unidadeOrganica = UnidadeOrganica::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Unidade orgânica criada com sucesso',
                'data' => $unidadeOrganica
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar unidade orgânica: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar uma unidade orgânica existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $unidadeOrganica = UnidadeOrganica::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:255',
                    Rule::unique('unidades_organica')->ignore($unidadeOrganica->id)
                ],
                'sigla' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('unidades_organica')->ignore($unidadeOrganica->id)
                ],
                'descricao' => 'nullable|string',
                'tipo' => 'nullable|string|max:50',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $unidadeOrganica->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Unidade orgânica atualizada com sucesso',
                'data' => $unidadeOrganica
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar unidade orgânica: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Excluir uma unidade orgânica
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $unidadeOrganica = UnidadeOrganica::findOrFail($id);
            $unidadeOrganica->ativo = false;
            $unidadeOrganica->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Unidade orgânica desativada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar unidade orgânica: ' . $e->getMessage()
            ], 500);
        }
    }
}