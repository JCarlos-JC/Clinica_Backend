<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrecoEspecialidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PrecoEspecialidadeController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/precos-especialidades
     */
    public function index(Request $request)
    {
        try {
            $query = PrecoEspecialidade::with(['especialidade', 'tipoUtente']);

            // Filtros
            if ($request->has('especialidade_id')) {
                $query->porEspecialidade($request->especialidade_id);
            }

            if ($request->has('tipo_utente_id')) {
                $query->porTipoUtente($request->tipo_utente_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->boolean('apenas_ativos')) {
                $query->ativo();
            }

            $precos = $query->orderBy('especialidade_id')
                ->orderBy('tipo_utente_id')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $precos
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar preços de especialidades', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar preços de especialidades'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/precos-especialidades
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'especialidade_id' => 'required|integer|exists:especialidades,id',
            'tipo_utente_id' => 'required|integer|exists:tipo_utentes,id',
            'valor' => 'required|numeric|min:0',
            'estado' => 'nullable|in:Ativo,Inativo',
            'descricao' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $preco = PrecoEspecialidade::create([
                'especialidade_id' => $request->especialidade_id,
                'tipo_utente_id' => $request->tipo_utente_id,
                'valor' => $request->valor,
                'estado' => $request->estado ?? 'Ativo',
                'descricao' => $request->descricao,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Preço de especialidade criado com sucesso',
                'data' => $preco->load(['especialidade', 'tipoUtente'])
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já existe um preço cadastrado para esta especialidade e tipo de utente'
                ], 409);
            }

            Log::error('Erro ao criar preço de especialidade', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar preço de especialidade'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/precos-especialidades/{id}
     */
    public function show(string $id)
    {
        try {
            $preco = PrecoEspecialidade::with(['especialidade', 'tipoUtente'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $preco
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Preço de especialidade não encontrado'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/precos-especialidades/{id}
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'especialidade_id' => 'sometimes|required|integer|exists:especialidades,id',
            'tipo_utente_id' => 'sometimes|required|integer|exists:tipo_utentes,id',
            'valor' => 'sometimes|required|numeric|min:0',
            'estado' => 'sometimes|required|in:Ativo,Inativo',
            'descricao' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $preco = PrecoEspecialidade::findOrFail($id);
            $preco->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Preço de especialidade atualizado com sucesso',
                'data' => $preco->load(['especialidade', 'tipoUtente'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Preço de especialidade não encontrado'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já existe um preço cadastrado para esta especialidade e tipo de utente'
                ], 409);
            }

            Log::error('Erro ao atualizar preço de especialidade', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar preço de especialidade'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/precos-especialidades/{id}
     */
    public function destroy(string $id)
    {
        try {
            $preco = PrecoEspecialidade::findOrFail($id);
            $preco->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Preço de especialidade excluído com sucesso'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Preço de especialidade não encontrado'
            ], 404);
        }
    }

    /**
     * Obter preço específico por especialidade e tipo de utente
     * GET /api/precos-especialidades/preco?especialidade_id=1&tipo_utente_id=1
     */
    public function getPreco(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'especialidade_id' => 'required|integer',
            'tipo_utente_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $preco = PrecoEspecialidade::porEspecialidade($request->especialidade_id)
                ->porTipoUtente($request->tipo_utente_id)
                ->ativo()
                ->with(['especialidade', 'tipoUtente'])
                ->first();

            if (!$preco) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Preço não encontrado para esta especialidade e tipo de utente'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $preco
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar preço de especialidade', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao buscar preço de especialidade'
            ], 500);
        }
    }
}
