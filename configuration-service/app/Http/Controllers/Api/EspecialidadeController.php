<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Especialidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EspecialidadeController extends Controller
{
    /**
     * Listar todas as especialidades
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Especialidade::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('requer_encaminhamento')) {
                $query->where('requer_encaminhamento', $request->boolean('requer_encaminhamento'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            }
            
            $especialidades = $query->orderBy('nome')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $especialidades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar especialidades: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter especialidades ativas
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActive()
    {
        try {
            $especialidades = Especialidade::where('ativo', true)
                ->orderBy('nome')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $especialidades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar especialidades ativas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter uma especialidade específica
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $especialidade = Especialidade::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $especialidade
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Especialidade não encontrada'
            ], 404);
        }
    }
    
    /**
     * Criar uma nova especialidade
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:especialidades',
            'codigo' => 'nullable|string|max:20|unique:especialidades',
            'descricao' => 'nullable|string',
            'requer_encaminhamento' => 'boolean',
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
            $especialidade = Especialidade::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Especialidade criada com sucesso',
                'data' => $especialidade
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar especialidade: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar uma especialidade existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $especialidade = Especialidade::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('especialidades')->ignore($especialidade->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('especialidades')->ignore($especialidade->id)
                ],
                'descricao' => 'nullable|string',
                'requer_encaminhamento' => 'boolean',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $especialidade->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Especialidade atualizada com sucesso',
                'data' => $especialidade
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar especialidade: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remover uma especialidade
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $especialidade = Especialidade::findOrFail($id);
            
            // Desativar em vez de excluir
            $especialidade->ativo = false;
            $especialidade->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Especialidade desativada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar especialidade: ' . $e->getMessage()
            ], 500);
        }
    }
}