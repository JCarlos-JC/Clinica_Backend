<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoExame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TipoExameController extends Controller
{
    /**
     * Listar todos os tipos de exame
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = TipoExame::query();
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            if ($request->has('categoria')) {
                $query->where('categoria', $request->categoria);
            }
            
            if ($request->has('requer_jejum')) {
                $query->where('requer_jejum', $request->boolean('requer_jejum'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%")
                      ->orWhere('categoria', 'like', "%{$search}%");
                });
            }
            
            $tiposExame = $query->orderBy('categoria')->orderBy('nome')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $tiposExame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar tipos de exame: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter tipos de exame por categoria
     *
     * @param string $categoria
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategoria($categoria, Request $request)
    {
        try {
            $query = TipoExame::where('categoria', $categoria);
            
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            $tiposExame = $query->orderBy('nome')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $tiposExame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar tipos de exame por categoria: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter um tipo de exame específico
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $tipoExame = TipoExame::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $tipoExame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de exame não encontrado'
            ], 404);
        }
    }
    
    /**
     * Criar um novo tipo de exame
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100|unique:tipos_exame',
            'codigo' => 'nullable|string|max:20|unique:tipos_exame',
            'descricao' => 'nullable|string',
            'categoria' => 'required|string|max:50',
            'preco_padrao' => 'required|numeric|min:0',
            'tempo_estimado_minutos' => 'nullable|integer|min:0',
            'requer_jejum' => 'boolean',
            'instrucoes_preparo' => 'nullable|string',
            'instrucoes_coleta' => 'nullable|string',
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
            $tipoExame = TipoExame::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Tipo de exame criado com sucesso',
                'data' => $tipoExame
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar tipo de exame: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Atualizar um tipo de exame existente
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $tipoExame = TipoExame::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'nome' => [
                    'sometimes', 
                    'required', 
                    'string', 
                    'max:100',
                    Rule::unique('tipos_exame')->ignore($tipoExame->id)
                ],
                'codigo' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('tipos_exame')->ignore($tipoExame->id)
                ],
                'descricao' => 'nullable|string',
                'categoria' => 'sometimes|required|string|max:50',
                'preco_padrao' => 'sometimes|required|numeric|min:0',
                'tempo_estimado_minutos' => 'nullable|integer|min:0',
                'requer_jejum' => 'boolean',
                'instrucoes_preparo' => 'nullable|string',
                'instrucoes_coleta' => 'nullable|string',
                'ativo' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $tipoExame->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Tipo de exame atualizado com sucesso',
                'data' => $tipoExame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar tipo de exame: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Excluir um tipo de exame
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $tipoExame = TipoExame::findOrFail($id);
            $tipoExame->ativo = false;
            $tipoExame->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Tipo de exame desativado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao desativar tipo de exame: ' . $e->getMessage()
            ], 500);
        }
    }
}