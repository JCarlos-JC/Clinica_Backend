<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrecoConsulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrecoConsultaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = PrecoConsulta::with(['tipoConsulta', 'tipoUtente']);
            
            // Filtrar por tipo de consulta
            if ($request->has('tipo_consulta_id')) {
                $query->porTipoConsulta($request->tipo_consulta_id);
            }
            
            // Filtrar por tipo de utente
            if ($request->has('tipo_utente_id')) {
                $query->porTipoUtente($request->tipo_utente_id);
            }
            
            // Filtrar por status ativo
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            $precos = $query->orderBy('tipo_consulta_id')
                           ->orderBy('tipo_utente_id')
                           ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $precos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar preços: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter preço específico por tipo de consulta e tipo de utente
     */
    public function getPreco(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_consulta_id' => 'required|exists:tipos_consulta,id',
                'tipo_utente_id' => 'required|exists:tipo_utentes,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $preco = PrecoConsulta::with(['tipoConsulta', 'tipoUtente'])
                ->porTipoConsulta($request->tipo_consulta_id)
                ->porTipoUtente($request->tipo_utente_id)
                ->ativo()
                ->first();
            
            if (!$preco) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Preço não encontrado para esta combinação'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $preco
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar preço: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_consulta_id' => 'required|exists:tipos_consulta,id',
                'tipo_utente_id' => 'required|exists:tipo_utentes,id',
                'valor' => 'required|numeric|min:0',
                'descricao' => 'nullable|string',
                'ativo' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verificar se já existe este preço
            $existe = PrecoConsulta::where('tipo_consulta_id', $request->tipo_consulta_id)
                ->where('tipo_utente_id', $request->tipo_utente_id)
                ->exists();
            
            if ($existe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já existe um preço cadastrado para esta combinação'
                ], 422);
            }
            
            $preco = PrecoConsulta::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Preço criado com sucesso',
                'data' => $preco->load(['tipoConsulta', 'tipoUtente'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar preço: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $preco = PrecoConsulta::with(['tipoConsulta', 'tipoUtente'])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $preco
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Preço não encontrado'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $preco = PrecoConsulta::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'tipo_consulta_id' => 'sometimes|required|exists:tipos_consulta,id',
                'tipo_utente_id' => 'sometimes|required|exists:tipo_utentes,id',
                'valor' => 'sometimes|required|numeric|min:0',
                'descricao' => 'nullable|string',
                'ativo' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $preco->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Preço atualizado com sucesso',
                'data' => $preco->load(['tipoConsulta', 'tipoUtente'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar preço: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $preco = PrecoConsulta::findOrFail($id);
            $preco->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Preço removido com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao remover preço: ' . $e->getMessage()
            ], 500);
        }
    }
}
