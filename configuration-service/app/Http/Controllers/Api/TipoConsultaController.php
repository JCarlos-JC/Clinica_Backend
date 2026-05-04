<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoConsulta;
use App\Models\PrecoConsulta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TipoConsultaController extends Controller
{
    /**
     * Listar todos os tipos de consulta
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = TipoConsulta::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('requer_triagem')) {
                $query->where('requer_triagem', $request->boolean('requer_triagem'));
            }
            
            if ($request->has('isento_pagamento')) {
                $query->where('isento_pagamento', $request->boolean('isento_pagamento'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            }
            
            $tiposConsulta = $query->orderBy('nome')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $tiposConsulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar tipos de consulta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter tipos de consulta ativos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActive()
    {
        try {
            $tiposConsulta = TipoConsulta::where('ativo', true)
                ->orderBy('nome')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $tiposConsulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar tipos de consulta ativos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter um tipo de consulta específico
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $tipoConsulta = TipoConsulta::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $tipoConsulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de consulta não encontrado'
            ], 404);
        }
    }
    
    /**
     * Criar um novo tipo de consulta
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:tipos_consulta',
            'codigo' => 'nullable|string|max:20|unique:tipos_consulta',
            'descricao' => 'nullable|string',
            'requer_triagem' => 'boolean',
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
            $tipoConsulta = TipoConsulta::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Tipo de consulta criado com sucesso',
                'data' => $tipoConsulta
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar tipo de consulta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar um tipo de consulta existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $tipoConsulta = TipoConsulta::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('tipos_consulta')->ignore($tipoConsulta->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('tipos_consulta')->ignore($tipoConsulta->id)
                ],
                'descricao' => 'nullable|string',
                'requer_triagem' => 'boolean',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $tipoConsulta->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Tipo de consulta atualizado com sucesso',
                'data' => $tipoConsulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar tipo de consulta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Desativar um tipo de consulta
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $tipoConsulta = TipoConsulta::findOrFail($id);
            $tipoConsulta->ativo = false;
            $tipoConsulta->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Tipo de consulta desativado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar tipo de consulta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getValor(Request $request, int $id): JsonResponse
    {
        $tipoUtenteId = $request->query('tipo_utente_id');
        
        if (!$tipoUtenteId) {
            return response()->json([
                'success' => false,
                'message' => 'tipo_utente_id é obrigatório'
            ], 400);
        }
        
        // Buscar valor na tabela de relacionamento
        $precoConsulta = PrecoConsulta::where('tipo_consulta_id', $id)
            ->where('tipo_utente_id', $tipoUtenteId)
            ->first();
            
        if (!$precoConsulta) {
            return response()->json([
                'success' => false,
                'message' => 'Preço não configurado para este tipo de consulta e utente'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'valor' => $precoConsulta->valor,
                'descricao' => $precoConsulta->descricao,
                'tipo_consulta' => $precoConsulta->tipoConsulta,
                'tipo_utente' => $precoConsulta->tipoUtente
            ]
        ]);
    }
}