<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetodoPagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MetodoPagamentoController extends Controller
{
    /**
     * Listar todos os métodos de pagamento
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = MetodoPagamento::query();
            
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
            
            $metodosPagamento = $query->orderBy('nome')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $metodosPagamento
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar métodos de pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter um método de pagamento específico
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $metodoPagamento = MetodoPagamento::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $metodoPagamento
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Método de pagamento não encontrado'
            ], 404);
        }
    }
    
    /**
     * Criar um novo método de pagamento
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:metodos_pagamento',
            'codigo' => 'nullable|string|max:20|unique:metodos_pagamento',
            'descricao' => 'nullable|string',
            'requer_comprovante' => 'boolean',
            'requer_confirmacao' => 'boolean',
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
            $metodoPagamento = MetodoPagamento::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Método de pagamento criado com sucesso',
                'data' => $metodoPagamento
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar método de pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar um método de pagamento existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $metodoPagamento = MetodoPagamento::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('metodos_pagamento')->ignore($metodoPagamento->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('metodos_pagamento')->ignore($metodoPagamento->id)
                ],
                'descricao' => 'nullable|string',
                'requer_comprovante' => 'boolean',
                'requer_confirmacao' => 'boolean',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $metodoPagamento->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Método de pagamento atualizado com sucesso',
                'data' => $metodoPagamento
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar método de pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Excluir um método de pagamento
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $metodoPagamento = MetodoPagamento::findOrFail($id);
            $metodoPagamento->ativo = false;
            $metodoPagamento->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Método de pagamento desativado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar método de pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
}