<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoConsulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EstadoConsultaController extends Controller
{
    /**
     * Listar todos os estados de consulta
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = EstadoConsulta::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('estado_final')) {
                $query->where('estado_final', $request->boolean('estado_final'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
                });
            }
            
            // Ordenar por ordem de exibição e depois por nome
            $estadosConsulta = $query->orderBy('ordem_exibicao')
                                    ->orderBy('nome')
                                    ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $estadosConsulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar estados de consulta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter um estado de consulta específico
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $estadoConsulta = EstadoConsulta::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $estadoConsulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Estado de consulta não encontrado'
            ], 404);
        }
    }
    
    /**
     * Criar um novo estado de consulta
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:estados_consulta',
            'codigo' => 'nullable|string|max:20|unique:estados_consulta',
            'descricao' => 'nullable|string',
            'cor' => 'nullable|string|max:20',
            'icone' => 'nullable|string|max:50',
            'estado_final' => 'boolean',
            'requer_encerramento_ciclo' => 'boolean',
            'ordem_exibicao' => 'integer',
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
            $estadoConsulta = EstadoConsulta::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Estado de consulta criado com sucesso',
                'data' => $estadoConsulta
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar estado de consulta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar um estado de consulta existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $estadoConsulta = EstadoConsulta::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('estados_consulta')->ignore($estadoConsulta->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('estados_consulta')->ignore($estadoConsulta->id)
                ],
                'descricao' => 'nullable|string',
                'cor' => 'nullable|string|max:20',
                'icone' => 'nullable|string|max:50',
                'estado_final' => 'boolean',
                'requer_encerramento_ciclo' => 'boolean',
                'ordem_exibicao' => 'integer',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $estadoConsulta->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Estado de consulta atualizado com sucesso',
                'data' => $estadoConsulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar estado de consulta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Desativar um estado de consulta
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $estadoConsulta = EstadoConsulta::findOrFail($id);
            $estadoConsulta->ativo = false;
            $estadoConsulta->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Estado de consulta desativado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar estado de consulta: ' . $e->getMessage()
            ], 500);
        }
    }
}