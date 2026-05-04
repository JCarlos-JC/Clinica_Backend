<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Exame::with(['tipoExame', 'tipoUtente']);
            
            // Filtrar por tipo de exame
            if ($request->has('tipo_exame_id')) {
                $query->porTipoExame($request->tipo_exame_id);
            }
            
            // Filtrar por tipo de utente
            if ($request->has('tipo_utente_id')) {
                $query->porTipoUtente($request->tipo_utente_id);
            }
            
            // Filtrar por status ativo
            if ($request->has('ativo')) {
                $query->where('ativo', $request->boolean('ativo'));
            }
            
            $exames = $query->orderBy('tipo_exame_id')
                           ->orderBy('tipo_utente_id')
                           ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $exames
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar exames: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter preço específico por tipo de exame e tipo de utente
     */
    public function getPreco(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_exame_id' => 'required|exists:tipos_exame,id',
                'tipo_utente_id' => 'required|exists:tipo_utentes,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $exame = Exame::with(['tipoExame', 'tipoUtente'])
                ->porTipoExame($request->tipo_exame_id)
                ->porTipoUtente($request->tipo_utente_id)
                ->ativo()
                ->first();
            
            if (!$exame) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Exame não encontrado para esta combinação'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $exame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar exame: ' . $e->getMessage()
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
                'tipo_exame_id' => 'required|exists:tipos_exame,id',
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
            
            // Verificar se já existe este exame
            $existe = Exame::where('tipo_exame_id', $request->tipo_exame_id)
                ->where('tipo_utente_id', $request->tipo_utente_id)
                ->exists();
            
            if ($existe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já existe um exame cadastrado para esta combinação'
                ], 422);
            }
            
            $exame = Exame::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Exame criado com sucesso',
                'data' => $exame->load(['tipoExame', 'tipoUtente'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar exame: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $exame = Exame::with(['tipoExame', 'tipoUtente'])->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $exame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exame não encontrado'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $exame = Exame::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'tipo_exame_id' => 'sometimes|required|exists:tipos_exame,id',
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
            
            $exame->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Exame atualizado com sucesso',
                'data' => $exame->load(['tipoExame', 'tipoUtente'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar exame: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $exame = Exame::findOrFail($id);
            $exame->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Exame removido com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao remover exame: ' . $e->getMessage()
            ], 500);
        }
    }
}
